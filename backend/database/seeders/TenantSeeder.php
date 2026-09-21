<?php

namespace Database\Seeders;

use App\Models\Tenant;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class TenantSeeder extends Seeder
{
    public function run(): void
    {
        $tenants = [
            [
                'id'     => 'garnet',
                'name'   => 'Garnet',
                'domain' => 'garnet.edu.gh',
            ],
            [
                'id'     => 'apba',
                'name'   => 'African Plant Breeders',
                'domain' => 'africanplantbreeders.edu.gh',
            ],
        ];

        foreach ($tenants as $data) {
            if (Tenant::find($data['id'])) {
                $this->command->warn("Tenant [{$data['id']}] already exists, skipping.");
                continue;
            }

            $tenant = new Tenant(['id' => $data['id'], 'name' => $data['name']]);
            $tenant->save();

            $tenant = Tenant::find($data['id']);

            $tenant->domains()->create(['domain' => $data['domain']]);

            $dbName = 'customer_' . $data['id'];
            $exists = DB::select("SELECT 1 FROM pg_database WHERE datname = ?", [$dbName]);
            if (!$exists) {
                DB::statement("CREATE DATABASE \"{$dbName}\"");
                $this->command->info("Database [{$dbName}] created.");
            }

            $tenant->run(function () use ($data) {
                Artisan::call('migrate', ['--path' => 'database/migrations/tenant', '--force' => true]);

                \App\Models\User::create([
                    'name'              => $data['name'] . ' Admin',
                    'email'             => 'admin@' . $data['domain'],
                    'password'          => Hash::make('password'),
                    'email_verified_at' => now(),
                ]);
            });

            $this->command->info("Tenant [{$data['id']}] created — admin: admin@{$data['domain']} / password");
        }
    }
}
