<?php

namespace App\Filament\Resources\UserResource\Pages;

use App\Filament\Resources\UserResource;
use Filament\Actions;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;
use Filament\Support\Exceptions\Halt;

class EditUser extends EditRecord
{
    protected static string $resource = UserResource::class;

    protected function getHeaderActions(): array
    {
        return [Actions\DeleteAction::make()->hidden(fn () => $this->record->getKey() === auth()->id())];
    }

    protected function beforeSave(): void
    {
        $problem = UserResource::removalProblem($this->record, (int) ($this->data['role_id'] ?? 0));
        if ($problem) {
            Notification::make()->title($problem)->body('Give another user the Administrator role first.')->danger()->send();

            throw new Halt();
        }
    }
}
