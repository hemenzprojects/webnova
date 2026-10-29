<?php

namespace App\Plugins\Membership\Filament\Resources;

use App\Admin\Concerns\InFunctionalArea;
use App\Models\Member;
use App\Plugins\Membership\Filament\Resources\MembershipRegistrationResource\Pages;
use App\Plugins\Membership\Models\MembershipForm;
use App\Plugins\Membership\Models\MembershipRegistration;
use App\Plugins\Membership\Models\MembershipType;
use App\Plugins\Membership\Support\FormSchema;
use App\Plugins\Membership\Support\ApplicationFiles;
use App\Plugins\Membership\Support\MemberAccounts;
use App\Plugins\Membership\Support\MembershipEmails;
use App\Plugins\Membership\Support\MembershipMailer;
use App\Plugins\Membership\Support\Registrar;
use Filament\Forms;
use Filament\Infolists;
use Filament\Infolists\Infolist;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Str;
use Throwable;

class MembershipRegistrationResource extends Resource
{
    use InFunctionalArea;

    protected static string $area = 'membership';

    protected static ?string $plugin = 'membership';

    protected static ?string $model = MembershipRegistration::class;

    protected static ?string $navigationIcon = 'heroicon-o-user-plus';

    protected static ?string $navigationLabel = 'Applications';

    protected static ?string $modelLabel = 'application';

    protected static ?int $navigationSort = 2;

    protected static ?string $recordTitleAttribute = 'name';

    public static function getNavigationBadge(): ?string
    {
        $pending = static::pluginIsActive() ? MembershipRegistration::where('status', 'pending')->count() : 0;

        return $pending ?: null;
    }

    public static function canCreate(): bool
    {
        // Registrations come in through the public form
        return false;
    }

    /** Columns shared with the dashboard's "Latest registrations" */
    public static function summaryColumns(): array
    {
        return [
            Tables\Columns\TextColumn::make('reference')->searchable()->copyable()->fontFamily('mono')->size('sm'),
            Tables\Columns\TextColumn::make('name')->searchable()->placeholder('—'),
            Tables\Columns\TextColumn::make('email')->searchable()->placeholder('—'),
            Tables\Columns\TextColumn::make('membershipType.name')->label('Type')->placeholder('—'),
            Tables\Columns\TextColumn::make('amount')
                ->formatStateUsing(fn ($state, MembershipRegistration $record) => $record->currency . ' ' . number_format((float) $state, 2)),
            Tables\Columns\TextColumn::make('payment_status')->label('Payment')->badge()
                ->formatStateUsing(fn ($state) => MembershipRegistration::PAYMENT_STATUSES[$state] ?? $state)
                ->color(fn ($state) => match ($state) { 'paid' => 'success', 'failed' => 'danger', 'unpaid' => 'warning', default => 'gray' }),
            Tables\Columns\TextColumn::make('status')->badge()
                ->formatStateUsing(fn ($state) => MembershipRegistration::STATUSES[$state] ?? $state)
                ->color(fn ($state) => match ($state) { 'approved' => 'success', 'rejected' => 'danger', default => 'warning' }),
            Tables\Columns\TextColumn::make('created_at')->label('Submitted')->dateTime('j M Y, H:i')->sortable(),
        ];
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns(static::summaryColumns())
            ->defaultSort('created_at', 'desc')
            ->filters([
                Tables\Filters\SelectFilter::make('membership_type_id')->label('Membership type')
                    ->options(fn () => MembershipType::orderBy('order')->pluck('name', 'id')),
                Tables\Filters\SelectFilter::make('status')->options(MembershipRegistration::STATUSES),
                Tables\Filters\SelectFilter::make('payment_status')->label('Payment')->options(MembershipRegistration::PAYMENT_STATUSES),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
                static::approveAction(Tables\Actions\Action::class),
            ])
            ->headerActions([
                Tables\Actions\Action::make('export')
                    ->label('Export CSV')
                    ->icon('heroicon-o-arrow-down-tray')
                    ->color('gray')
                    ->action(fn ($livewire) => static::exportCsv($livewire->getFilteredSortedTableQuery()->get())),
            ])
            ->bulkActions([
                Tables\Actions\BulkAction::make('approve')
                    ->authorize(fn () => static::canManage())
                    ->label('Approve selected')
                    ->icon('heroicon-o-check')
                    ->requiresConfirmation()
                    ->action(fn (Collection $records) => $records->each->update(['status' => 'approved', 'reviewed_at' => now()]))
                    ->deselectRecordsAfterCompletion(),
                Tables\Actions\BulkAction::make('export')
                    ->label('Export selected')
                    ->icon('heroicon-o-arrow-down-tray')
                    ->action(fn (Collection $records) => static::exportCsv($records)),
                Tables\Actions\DeleteBulkAction::make(),
            ]);
    }

    public static function infolist(Infolist $infolist): Infolist
    {
        /** @var MembershipRegistration $record */
        $record = $infolist->getRecord();

        // Documents link to their admin-only download
        $fileKeys = collect(FormSchema::fields($record->form_snapshot ?? []))
            ->filter(fn ($field) => ApplicationFiles::isFile(($record->answers ?? [])[$field['key']] ?? null))
            ->pluck('key', 'label');

        $answers = collect($record->readableAnswers())
            ->map(fn ($value, $label) => Infolists\Components\TextEntry::make('answer_' . Str::slug($label, '_'))
                ->label($label)
                ->state($value)
                ->placeholder('—')
                ->when($fileKeys->has($label), fn ($entry) => $entry
                    ->icon('heroicon-o-paper-clip')
                    ->color('primary')
                    ->url('/api/v1/membership/registrations/' . rawurlencode($record->reference) . '/files/' . rawurlencode($fileKeys[$label]))
                    ->openUrlInNewTab()))
            ->values()
            ->all();

        return $infolist->schema([
            Infolists\Components\Section::make('Application')
                ->columns(4)
                ->schema([
                    Infolists\Components\TextEntry::make('reference')->copyable()->fontFamily('mono'),
                    Infolists\Components\TextEntry::make('membershipType.name')->label('Membership type')->placeholder('—'),
                    Infolists\Components\TextEntry::make('amount')
                        ->formatStateUsing(fn ($state) => $record->currency . ' ' . number_format((float) $state, 2)),
                    Infolists\Components\TextEntry::make('created_at')->label('Submitted')->dateTime('j M Y, H:i'),
                    Infolists\Components\TextEntry::make('status')->badge()
                        ->formatStateUsing(fn ($state) => MembershipRegistration::STATUSES[$state] ?? $state)
                        ->color(fn ($state) => match ($state) { 'approved' => 'success', 'rejected' => 'danger', default => 'warning' }),
                    Infolists\Components\TextEntry::make('payment_status')->label('Payment')->badge()
                        ->formatStateUsing(fn ($state) => MembershipRegistration::PAYMENT_STATUSES[$state] ?? $state)
                        ->color(fn ($state) => match ($state) { 'paid' => 'success', 'failed' => 'danger', 'unpaid' => 'warning', default => 'gray' }),
                    Infolists\Components\TextEntry::make('paid_at')->label('Paid on')->dateTime('j M Y, H:i')->placeholder('—'),
                    Infolists\Components\TextEntry::make('payment_reference')->label('Paystack reference')->placeholder('—')->fontFamily('mono'),
                    Infolists\Components\TextEntry::make('decision_reason')
                        ->label('Reason given to the applicant')
                        ->columnSpanFull()
                        ->visible(fn () => $record->status === 'rejected' && filled($record->decision_reason)),
                    Infolists\Components\TextEntry::make('memberAccount.member_number')
                        ->label('Member account')
                        ->fontFamily('mono')
                        ->url(fn () => $record->memberAccount && MemberAccountResource::canAccessArea() ? MemberAccountResource::getUrl('edit', ['record' => $record->memberAccount]) : null)
                        ->visible(fn () => (bool) $record->memberAccount),
                ]),
            Infolists\Components\Section::make('Answers')
                ->description($record->form_version !== MembershipForm::current()->version
                    ? 'Shown as the form was when this registration was submitted.'
                    : null)
                ->columns(2)
                ->schema($answers),
            Infolists\Components\Section::make('Notes')
                ->schema([
                    Infolists\Components\TextEntry::make('admin_notes')->hiddenLabel()->placeholder('No notes yet.'),
                    Infolists\Components\TextEntry::make('member.name')
                        ->label('In the Members directory as')
                        ->url(fn () => $record->member_id ? \App\Filament\Resources\MemberResource::getUrl('edit', ['record' => $record->member_id]) : null)
                        ->visible(fn () => (bool) $record->member_id),
                ]),
        ]);
    }

    /**
     * @param  class-string<\Filament\Actions\Action>|class-string<Tables\Actions\Action>  $class
     */
    public static function approveAction(string $class)
    {
        return $class::make('approve')
            ->authorize(fn () => static::canManage())
            ->label('Approve')
            ->icon('heroicon-o-check')
            ->color('success')
            ->visible(fn (MembershipRegistration $record) => $record->status !== 'approved')
            ->requiresConfirmation()
            ->modalDescription(fn (MembershipRegistration $record) => in_array($record->payment_status, ['unpaid', 'failed'], true)
                ? 'This application has not been paid yet. Approve it anyway?'
                    . (app(MemberAccounts::class)->enabled() ? ' The member account and welcome email follow once it is marked as paid.' : '')
                : null)
            ->action(function (MembershipRegistration $record) {
                $record->update(['status' => 'approved', 'reviewed_at' => now()]);
                $account = $record->memberAccount()->first();
                Notification::make()->title('Application approved')
                    ->body($account
                        ? "Member account {$account->member_number} is ready" . ($account->hasPassword() ? '.' : '; the applicant was emailed a link to set a password.')
                        : (app(MemberAccounts::class)->enabled() && in_array($record->payment_status, ['unpaid', 'failed'], true)
                            ? 'No member account yet: it is created, and the welcome email sent, once the application is marked as paid.'
                            : null))
                    ->success()->send();
            });
    }

    /**
     * Header actions for the view page.
     *
     * @return array<\Filament\Actions\Action>
     */
    public static function recordActions(): array
    {
        return [
            static::approveAction(\Filament\Actions\Action::class),

            \Filament\Actions\Action::make('reject')
                ->authorize(fn () => static::canManage())
                ->label('Reject')
                ->icon('heroicon-o-x-mark')
                ->color('danger')
                ->visible(fn (MembershipRegistration $record) => $record->status !== 'rejected')
                ->modalHeading('Reject this application')
                ->modalSubmitActionLabel('Reject')
                ->form(fn (MembershipRegistration $record) => [
                    Forms\Components\Textarea::make('reason')
                        ->label('Reason (sent to the applicant)')
                        ->helperText('Optional. Included in the email, e.g. "The supporting documents were not included."')
                        ->rows(3),
                    Forms\Components\Toggle::make('notify')
                        ->label('Email the applicant')
                        ->default(filled($record->email) && MembershipEmails::template('rejected')['enabled'])
                        ->disabled(blank($record->email) || ! MembershipEmails::template('rejected')['enabled'])
                        ->helperText(blank($record->email)
                            ? 'This application has no email address.'
                            : (! MembershipEmails::template('rejected')['enabled'] ? 'The rejection email is switched off under Membership → Emails.' : 'Uses the "Application not accepted" email (Membership → Emails).')),
                    Forms\Components\Textarea::make('note')
                        ->label('Internal note (not sent)')
                        ->rows(2),
                ])
                ->action(function (MembershipRegistration $record, array $data) {
                    $record->update([
                        'status' => 'rejected',
                        'reviewed_at' => now(),
                        'decision_reason' => filled($data['reason'] ?? null) ? trim($data['reason']) : null,
                        'admin_notes' => trim(($record->admin_notes ? $record->admin_notes . "\n" : '') . (filled($data['note'] ?? null) ? $data['note'] : '')) ?: null,
                    ]);
                    $sent = ($data['notify'] ?? false) && app(MembershipMailer::class)->applicationRejected($record);
                    Notification::make()->title('Application rejected')
                        ->body($sent ? "The applicant was emailed at {$record->email}." : 'No email was sent.')
                        ->send();
                }),

            \Filament\Actions\ActionGroup::make([
                \Filament\Actions\Action::make('markPaid')
                    ->authorize(fn () => static::canManage())
                    ->label('Mark as paid')
                    ->icon('heroicon-o-banknotes')
                    ->visible(fn (MembershipRegistration $record) => in_array($record->payment_status, ['unpaid', 'failed'], true))
                    ->form([Forms\Components\TextInput::make('payment_reference')->label('Receipt or transfer reference (optional)')])
                    ->action(function (MembershipRegistration $record, array $data) {
                        $record->update([
                            'payment_status' => 'paid',
                            'paid_at' => now(),
                            'payment_reference' => $data['payment_reference'] ?: $record->payment_reference,
                        ]);
                        $account = $record->memberAccount()->first();
                        Notification::make()->title('Marked as paid')
                            ->body($account && ! $account->hasPassword()
                                ? "Member account {$account->member_number} is ready; a welcome email was sent to set a password."
                                : ($record->status !== 'approved' && app(MemberAccounts::class)->enabled() ? 'The member account is created once the application is approved.' : null))
                            ->success()->send();
                    }),

                \Filament\Actions\Action::make('checkPayment')
                    ->authorize(fn () => static::canManage())
                    ->label('Check payment with Paystack')
                    ->icon('heroicon-o-arrow-path')
                    ->visible(fn (MembershipRegistration $record) => $record->payment_reference && $record->payment_status !== 'paid')
                    ->action(function (MembershipRegistration $record) {
                        try {
                            $record = app(Registrar::class)->confirmPayment($record);
                        } catch (Throwable $e) {
                            Notification::make()->title('Paystack could not be checked')->body($e->getMessage())->danger()->send();

                            return;
                        }
                        Notification::make()->title('Payment status: ' . (MembershipRegistration::PAYMENT_STATUSES[$record->payment_status] ?? $record->payment_status))->send();
                    }),

                \Filament\Actions\Action::make('addToDirectory')
                    ->authorize(fn () => static::canManage())
                    ->label('Add to Members directory')
                    ->icon('heroicon-o-building-library')
                    ->visible(fn (MembershipRegistration $record) => ! $record->member_id && $record->status === 'approved')
                    ->requiresConfirmation()
                    ->modalDescription('Creates an entry in the public Members directory from this registration. You can edit it afterwards.')
                    ->action(function (MembershipRegistration $record) {
                        $member = static::createDirectoryEntry($record);
                        Notification::make()->title('Added to the Members directory')
                            ->body("\"{$member->name}\" was created as inactive; review it and switch it on to publish.")
                            ->success()->send();
                    }),

                \Filament\Actions\Action::make('notes')
                    ->authorize(fn () => static::canManage())
                    ->label('Edit notes')
                    ->icon('heroicon-o-pencil-square')
                    ->fillForm(fn (MembershipRegistration $record) => ['admin_notes' => $record->admin_notes])
                    ->form([Forms\Components\Textarea::make('admin_notes')->label('Notes (only visible to admins)')->rows(5)])
                    ->action(fn (MembershipRegistration $record, array $data) => $record->update($data)),

                \Filament\Actions\DeleteAction::make(),
            ]),
        ];
    }

    private static function createDirectoryEntry(MembershipRegistration $record): Member
    {
        $answers = $record->answers ?? [];
        $fields = collect(FormSchema::fields($record->form_snapshot ?? []));
        $answerOf = fn (string $type) => ($field = $fields->firstWhere('type', $type)) ? ($answers[$field['key']] ?? null) : null;

        $name = $record->name ?: $record->reference;
        $slug = Str::slug($name);
        if (Member::where('slug', $slug)->exists()) {
            $slug .= '-' . Str::lower(Str::random(4));
        }

        $member = Member::create([
            'name' => $name,
            'slug' => $slug,
            'type' => 'other',
            'email' => $record->email,
            'phone' => $answerOf('tel'),
            'location' => $answerOf('country'),
            // Not public until an admin has looked at it
            'is_active' => false,
        ]);

        $record->update(['member_id' => $member->id]);

        return $member;
    }

    /**
     * CSV with one column per form field (current form, plus any older fields found).
     */
    public static function exportCsv($records)
    {
        $records = $records instanceof Collection ? $records->load('membershipType') : collect($records);

        $fields = collect(FormSchema::fields(MembershipForm::current()->schema))
            ->concat($records->flatMap(fn ($r) => FormSchema::fields($r->form_snapshot ?? [])))
            ->unique('key')
            ->reject(fn ($field) => $field['type'] === 'membership_type')
            ->values();

        $filename = 'membership-registrations-' . now()->format('Y-m-d') . '.csv';

        return response()->streamDownload(function () use ($records, $fields) {
            $out = fopen('php://output', 'w');
            fputcsv($out, array_merge(['Reference', 'Submitted', 'Membership type', 'Amount', 'Currency', 'Payment', 'Status'], $fields->pluck('label')->all()));
            // Applicant-typed text must not run as a formula when the file is opened in a spreadsheet
            $safe = fn ($value) => is_string($value) && preg_match('/^[=+\-@\t\r]/', $value) ? "'" . $value : $value;
            foreach ($records as $r) {
                $row = [
                    $r->reference, $r->created_at?->format('Y-m-d H:i'), $r->membershipType?->name, $r->amount, $r->currency,
                    MembershipRegistration::PAYMENT_STATUSES[$r->payment_status] ?? $r->payment_status,
                    MembershipRegistration::STATUSES[$r->status] ?? $r->status,
                ];
                foreach ($fields as $field) {
                    $value = $r->answers[$field['key']] ?? '';
                    $row[] = is_array($value) ? implode(', ', $value) : (is_bool($value) ? ($value ? 'Yes' : 'No') : $value);
                }
                fputcsv($out, array_map($safe, $row));
            }
            fclose($out);
        }, $filename, ['Content-Type' => 'text/csv']);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListMembershipRegistrations::route('/'),
            'view' => Pages\ViewMembershipRegistration::route('/{record}'),
        ];
    }
}
