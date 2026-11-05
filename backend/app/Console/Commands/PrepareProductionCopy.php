<?php

namespace App\Console\Commands;

use App\Models\Tenant;
use Illuminate\Console\Command;
use Illuminate\Encryption\Encrypter;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;
use Throwable;

/**
 * After `make import-production` has copied the production databases here:
 * makes the copy usable and safe locally. Settings encrypted with
 * production's app key are re-encrypted with this one, payment keys are
 * removed (no real payments from a local copy), the platform's mail server is
 * switched off (local mail stays in Mailpit), and each site gets a
 * {site}.localhost address. Refuses to run in production.
 *
 * The production key comes from the PRODUCTION_APP_KEY environment variable,
 * never from a file or the command line.
 */
class PrepareProductionCopy extends Command
{
    protected $signature = 'webnova:prepare-production-copy';

    protected $description = 'Re-encrypt and strip secrets from a copy of the production databases (local only)';

    /** Setting names that hold secrets: dropped from every copied plugin's settings */
    private const SECRET = '/(secret|api_key|password|token|private)/i';

    public function handle(): int
    {
        if (app()->environment('production')) {
            $this->error('This is for local copies only: refusing to run in production.');

            return self::FAILURE;
        }
        $key = (string) getenv('PRODUCTION_APP_KEY');
        if ($key === '') {
            $this->error('PRODUCTION_APP_KEY is not set.');

            return self::FAILURE;
        }
        $production = new Encrypter(base64_decode(str_starts_with($key, 'base64:') ? substr($key, 7) : $key), config('app.cipher'));

        // Platform email: kept for reference, switched off and without its password
        foreach (DB::connection('central')->table('platform_settings')->get() as $row) {
            $value = $this->decrypt($production, $row->value);
            if ($value === null) {
                continue;
            }
            if ($row->key === 'mail') {
                $value = ['enabled' => false, 'password' => null] + $value;
            }
            // Written directly: loading the model would try to decrypt the old value with this site's key
            DB::connection('central')->table('platform_settings')->where('id', $row->id)->update(['value' => Crypt::encryptString(json_encode($value))]);
            $this->line("platform setting {$row->key}: re-encrypted" . ($row->key === 'mail' ? ', mail server switched off' : ''));
        }

        foreach (Tenant::with('domains')->get() as $tenant) {
            $tenant->run(function () use ($tenant, $production) {
                foreach (DB::table('tenant_plugins')->whereNotNull('settings')->get() as $row) {
                    $settings = $this->decrypt($production, $row->settings);
                    if ($settings === null) {
                        continue;
                    }
                    $removed = array_keys(array_filter($settings, fn ($v, $k) => preg_match(self::SECRET, $k) && filled($v), ARRAY_FILTER_USE_BOTH));
                    $settings = array_diff_key($settings, array_flip($removed));
                    // Stanbic's outlet goes too: no payments to the live outlet from a local copy
                    unset($settings['ngenius_outlet'], $settings['ngenius_api_url']);
                    // As the "encrypted:array" cast stores it, with this site's key
                    DB::table('tenant_plugins')->where('id', $row->id)->update(['settings' => Crypt::encryptString(json_encode($settings))]);
                    $this->line("{$tenant->id}: {$row->key} settings re-encrypted" . ($removed ? ' (removed: ' . implode(', ', $removed) . ')' : ''));
                }
            });

            // A local address next to the real domain (browsers send *.localhost to this computer)
            $local = strtolower($tenant->id) . '.localhost';
            if (! $tenant->domains->contains('domain', $local)) {
                $tenant->domains()->create(['domain' => $local]);
                $this->line("{$tenant->id}: local address http://{$local}:8080 added");
            }
        }

        $this->info('Done. Payment keys removed and platform email switched off: this copy cannot take payments or send real email.');

        return self::SUCCESS;
    }

    /** The decrypted array, or null if it was not encrypted with production's key (e.g. already local) */
    private function decrypt(Encrypter $production, ?string $value): ?array
    {
        if ($value === null || $value === '') {
            return null;
        }
        try {
            $plain = $production->decryptString($value);
        } catch (Throwable) {
            return null;
        }
        $decoded = json_decode($plain, true);

        return is_array($decoded) ? $decoded : null;
    }
}
