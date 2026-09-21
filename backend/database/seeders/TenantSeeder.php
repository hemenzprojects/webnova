<?php

namespace Database\Seeders;

use App\Models\Tenant;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Artisan;

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
        ];

        foreach ($tenants as $data) {
            if (Tenant::find($data['id'])) {
                $this->command->warn("Tenant [{$data['id']}] already exists, skipping.");
                continue;
            }

            $tenant = Tenant::create([
                'id'   => $data['id'],
                'name' => $data['name'],
            ]);

            $tenant->domains()->create(['domain' => $data['domain']]);

            $tenant->run(function () {
                Artisan::call('migrate', ['--force' => true]);
            });

            $this->command->info("Tenant [{$data['id']}] created with domain [{$data['domain']}].");
        }
    }
}