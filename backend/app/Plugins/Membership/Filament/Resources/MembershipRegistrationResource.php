<?php

namespace App\Plugins\Membership\Filament\Resources;

use App\Admin\Concerns\InFunctionalArea;
use App\Models\Member;
use App\Plugins\Membership\Filament\Resources\MembershipRegistrationResource\Pages;
use App\Plugins\Membership\Models\MembershipForm;
use App\Plugins\Membership\Models\MembershipRegistration;
use App\Plugins\Membership\Models\MembershipType;
use App\Plugins\Membership\Support\FormSchema;
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

    protected static ?string $navigationLabel = 'Registrations';

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

        $answers = collect($record->readableAnswers())
            ->map(fn ($value, $label) => Infolists\Components\TextEntry::make('answer_' . Str::slug($label, '_'))
                ->label($label)
                ->state($value)
                ->placeholder('—'))
            ->values()
            ->all();

        return $infolist->schema([
            Infolists\Components\Section::make('Registration')
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
            ->modalDescription(fn (MembershipRegistration $record) => $record->payment_status === 'unpaid'
                ? 'This registration has not been paid yet. Approve it anyway?'
                : null)
            ->action(function (MembershipRegistration $record) {
                $record->update(['status' => 'approved', 'reviewed_at' => now()]);
                Notification::make()->title('Registration approved')->success()->send();
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
                ->form([Forms\Components\Textarea::make('reason')->label('Reason (kept in the notes)')])
                ->action(function (MembershipRegistration $record, array $data) {
                    $record->update([
                        'status' => 'rejected',
                        'reviewed_at' => now(),
                        'admin_notes' => trim(($record->admin_notes ? $record->admin_notes . "\n" : '') . ($data['reason'] ? 'Rejected: ' . $data['reason'] : '')) ?: null,
                    ]);
                    Notification::make()->title('Registration rejected')->send();
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
                        Notification::make()->title('Marked as paid')->success()->send();
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
                fputcsv($out, $row);
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
