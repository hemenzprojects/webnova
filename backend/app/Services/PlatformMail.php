<?php

namespace App\Services;

use App\Models\PlatformSetting;
use Illuminate\Mail\MailManager;
use Illuminate\Support\Facades\Mail;
use Throwable;

/**
 * The platform's outgoing email server (central admin → Platform → Email
 * settings), used by every site. Saved settings replace the server's MAIL_…
 * settings; while none are saved (or they are switched off) those are used.
 *
 * Applied when the mail manager is first needed in a request or job
 * (AppServiceProvider), so a site that sends no email pays nothing. Long-running
 * workers pick up changes when they restart (queue:work restarts hourly).
 */
class PlatformMail
{
    public const KEY = 'mail';

    public const MAILER = 'platform';

    /** The server's own mail.default and mail.from, kept while the platform's are in use */
    private static ?array $server = null;

    public static function settings(): array
    {
        return PlatformSetting::read(self::KEY) + [
            'enabled' => false, 'host' => null, 'port' => 587, 'username' => null, 'password' => null,
            'from_address' => null, 'from_name' => null,
        ];
    }

    public static function isActive(?array $settings = null): bool
    {
        $settings ??= self::settings();

        return (bool) $settings['enabled'] && filled($settings['host']) && filled($settings['from_address']);
    }

    /** A Laravel smtp mailer config; port 465 uses SSL, other ports STARTTLS when the server offers it */
    public static function mailerConfig(array $settings): array
    {
        return [
            'transport' => 'smtp',
            'host' => trim((string) $settings['host']),
            'port' => (int) ($settings['port'] ?: 587),
            'username' => filled($settings['username']) ? trim((string) $settings['username']) : null,
            'password' => filled($settings['password']) ? (string) $settings['password'] : null,
            'timeout' => 20,
            'local_domain' => parse_url((string) config('app.url'), PHP_URL_HOST) ?: null,
        ];
    }

    /** Use the saved settings for this request or job. Never breaks mail: on any problem the server settings stay. */
    public static function apply(): void
    {
        try {
            $settings = self::settings();
        } catch (Throwable $e) {
            report($e);

            return;
        }
        if (! self::isActive($settings)) {
            return;
        }

        self::$server ??= ['mail.default' => config('mail.default'), 'mail.from' => config('mail.from')];
        config([
            'mail.mailers.' . self::MAILER => self::mailerConfig($settings),
            'mail.default' => self::MAILER,
            'mail.from' => ['address' => $settings['from_address'], 'name' => $settings['from_name'] ?: config('app.name')],
        ]);
    }

    /**
     * Log in to the mail server with these settings, without sending anything.
     *
     * @throws Throwable with the server's answer when it refuses
     */
    public static function checkConnection(array $settings): void
    {
        /** @var \Symfony\Component\Mailer\Transport\Smtp\SmtpTransport $transport */
        $transport = app(MailManager::class)->createSymfonyTransport(self::mailerConfig($settings));
        $transport->start();
        $transport->stop();
    }

    /** Send a test message with these settings (saved or not) */
    public static function sendTest(array $settings, string $to): void
    {
        $mailer = Mail::build(self::mailerConfig($settings));
        $mailer->alwaysFrom($settings['from_address'], $settings['from_name'] ?: config('app.name'));
        $mailer->raw("This is a test email from the platform's email settings.\n\nIf you can read it, the settings work: every site that has no email server of its own sends through them.", function ($message) use ($to) {
            $message->to($to)->subject('Test email from ' . config('app.name'));
        });
    }

    /** Save, and use the new settings straight away in this request */
    public static function save(array $settings): void
    {
        PlatformSetting::write(self::KEY, $settings);
        if (self::$server) {
            config(self::$server);
        }
        app(MailManager::class)->forgetMailers();
        self::apply();
    }
}
