<?php

namespace App\Plugins\Membership\Filament\Resources\MemberAccountResource\Pages;

use App\Plugins\Membership\Filament\Resources\MemberAccountResource;
use App\Plugins\Membership\Support\MemberAccounts;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Database\Eloquent\Model;

/** An existing member added by hand: gets a Member ID and, if ticked, the welcome email */
class CreateMemberAccount extends CreateRecord
{
    protected static string $resource = MemberAccountResource::class;

    protected function handleRecordCreation(array $data): Model
    {
        $accounts = app(MemberAccounts::class);
        $account = $accounts->create($data);

        if ($this->data['send_welcome'] ?? false) {
            $accounts->sendWelcome($account)
                ? Notification::make()->title('Welcome email sent to ' . $account->email)->success()->send()
                : Notification::make()->title('The welcome email could not be sent')->body('You can resend it from the member\'s menu.')->warning()->send();
        }

        return $account;
    }

    protected function getCreatedNotification(): ?Notification
    {
        return null;
    }

    protected function getRedirectUrl(): string
    {
        return static::getResource()::getUrl('index');
    }
}
