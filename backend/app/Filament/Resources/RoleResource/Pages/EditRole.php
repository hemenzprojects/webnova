<?php

namespace App\Filament\Resources\RoleResource\Pages;

use App\Admin\Areas;
use App\Filament\Resources\RoleResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditRole extends EditRecord
{
    protected static string $resource = RoleResource::class;

    protected function getHeaderActions(): array
    {
        return [Actions\DeleteAction::make()->hidden(fn () => $this->record->is_admin || $this->record->users()->exists())];
    }

    protected function mutateFormDataBeforeFill(array $data): array
    {
        // Items without a saved level show as "None"
        foreach (Areas::members() as $class) {
            $data['permissions'][$class::permissionKey()] ??= 'none';
        }

        return $data;
    }

    protected function mutateFormDataBeforeSave(array $data): array
    {
        if ($this->record->is_admin) {
            unset($data['permissions']);

            return $data;
        }

        return RoleResource::cleanPermissions($data);
    }
}
