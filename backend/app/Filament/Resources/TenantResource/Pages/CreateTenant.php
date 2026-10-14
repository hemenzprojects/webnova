<?php

namespace App\Filament\Resources\TenantResource\Pages;

use App\Filament\Resources\TenantResource;
use App\Models\Role;
use App\Models\User;
use Filament\Resources\Pages\CreateRecord;

class CreateTenant extends CreateRecord
{
    protected static string $resource = TenantResource::class;

    protected function afterCreate(): void
    {
        // Creating the tenant has already made and migrated its database (TenantCreated pipeline)
        $this->record->domains()->create(['domain' => $this->data['domain']]);

        $email = $this->data['admin_email'];
        $password = $this->data['admin_password'];
        $name = $this->record->name;

        $this->record->run(fn () => User::create([
            'name' => "{$name} Admin",
            'email' => $email,
            'password' => $password,
            'role_id' => Role::where('is_admin', true)->value('id'),
        ]));
    }
}
