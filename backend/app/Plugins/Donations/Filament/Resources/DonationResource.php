<?php

namespace App\Plugins\Donations\Filament\Resources;

use App\Admin\Concerns\InFunctionalArea;
use App\Plugins\Donations\Filament\Resources\DonationResource\Pages;
use App\Plugins\Donations\Models\Donation;
use App\Plugins\Donations\Support\DonationSettings;
use App\Plugins\Donations\Support\Donor;
use App\Plugins\Membership\Support\Paystack;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Infolists;
use Filament\Infolists\Infolist;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Throwable;

class DonationResource extends Resource
{
    use InFunctionalArea;

    protected static string $area = 'donations';

    protected static ?string $plugin = 'donations';

    protected static ?string $model = Donation::class;

    // "donations" is the Overview page
    protected static ?string $slug = 'donation-records';

    protected static ?string $navigationIcon = 'heroicon-o-gift';

    protected static ?string $navigationLabel = 'All donations';

    protected static ?int $navigationSort = 2;

    protected static ?string $recordTitleAttribute = 'reference';

    /**
     * For a gift that did not come through the website: cash, a bank transfer, a cheque.
     */
    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Section::make('Donation')
                ->columns(3)
                ->schema([
                    Forms\Components\TextInput::make('amount')->numeric()->minValue(0.01)->required(),
                    Forms\Components\Select::make('currency')
                        ->options(Paystack::CURRENCIES)
                        ->default(fn () => DonationSettings::defaultCurrency())
                        ->required()
                        ->native(false),
                    Forms\Components\Select::make('method')
                        ->label('Received by')
                        ->options(collect(Donation::METHODS)->except('online'))
                        ->default('cash')
                        ->required()
                        ->native(false),
                    Forms\Components\DateTimePicker::make('paid_at')
                        ->label('Received on')
                        ->default(fn () => now())
                        ->seconds(false)
                        ->required(),
                    Forms\Components\TextInput::make('payment_reference')
                        ->label('Receipt or transfer reference')
                        ->columnSpan(2),
                ]),
            Forms\Components\Section::make('Donor')
                ->columns(2)
                ->schema([
                    Forms\Components\Select::make('title')
                        ->options(fn () => array_combine($titles = (array) DonationSettings::get('titles'), $titles))
                        ->native(false),
                    Forms\Components\TextInput::make('first_name')->required()->maxLength(100)->columnStart(1),
                    Forms\Components\TextInput::make('last_name')->required()->maxLength(100),
                    Forms\Components\TextInput::make('email')->email()->maxLength(255),
                    Forms\Components\TextInput::make('phone')->tel()->maxLength(30),
                    Forms\Components\Textarea::make('admin_notes')->label('Notes (only visible to admins)')->rows(3)->columnSpanFull(),
                ]),
        ]);
    }

    /** Columns shared with the dashboard's "Latest donations" */
    public static function summaryColumns(): array
    {
        return [
            Tables\Columns\TextColumn::make('reference')->searchable()->copyable()->fontFamily('mono')->size('sm'),
            Tables\Columns\TextColumn::make('donor_name')->label('Donor')
                ->searchable(query: fn (Builder $query, string $search) => $query->where(fn (Builder $q) => $q
                    ->where('first_name', 'ilike', "%{$search}%")->orWhere('last_name', 'ilike', "%{$search}%"))),
            Tables\Columns\TextColumn::make('email')->searchable()->placeholder('—'),
            Tables\Columns\TextColumn::make('amount')->sortable()->alignEnd()
                ->formatStateUsing(fn ($state, Donation $record) => $record->amount_formatted),
            Tables\Columns\TextColumn::make('method')->label('Received by')
                ->formatStateUsing(fn ($state) => Donation::METHODS[$state] ?? $state),
            Tables\Columns\TextColumn::make('payment_status')->label('Status')->badge()
                ->formatStateUsing(fn ($state) => Donation::PAYMENT_STATUSES[$state] ?? $state)
                ->color(fn ($state) => match ($state) { 'paid' => 'success', 'failed' => 'danger', default => 'warning' }),
            Tables\Columns\TextColumn::make('created_at')->label('Date')->dateTime('j M Y, H:i')->sortable(),
        ];
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns(static::summaryColumns())
            ->defaultSort('created_at', 'desc')
            ->filters([
                Tables\Filters\SelectFilter::make('payment_status')->label('Status')->options(Donation::PAYMENT_STATUSES),
                Tables\Filters\SelectFilter::make('method')->label('Received by')->options(Donation::METHODS),
                Tables\Filters\SelectFilter::make('currency')
                    ->options(fn () => Donation::query()->distinct()->pluck('currency', 'currency')),
                Tables\Filters\Filter::make('date')
                    ->form([
                        Forms\Components\DatePicker::make('from'),
                        Forms\Components\DatePicker::make('until'),
                    ])
                    ->query(fn (Builder $query, array $data) => $query
                        ->when($data['from'] ?? null, fn (Builder $q, $date) => $q->whereDate('created_at', '>=', $date))
                        ->when($data['until'] ?? null, fn (Builder $q, $date) => $q->whereDate('created_at', '<=', $date))),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
            ])
            ->headerActions([
                Tables\Actions\Action::make('export')
                    ->label('Export CSV')
                    ->icon('heroicon-o-arrow-down-tray')
                    ->color('gray')
                    ->action(fn ($livewire) => static::exportCsv($livewire->getFilteredSortedTableQuery()->get())),
            ])
            ->bulkActions([
                Tables\Actions\BulkAction::make('export')
                    ->label('Export selected')
                    ->icon('heroicon-o-arrow-down-tray')
                    ->action(fn (Collection $records) => static::exportCsv($records)),
                Tables\Actions\DeleteBulkAction::make(),
            ]);
    }

    public static function infolist(Infolist $infolist): Infolist
    {
        /** @var Donation $record */
        $record = $infolist->getRecord();

        // Everything this person has given, matched by email
        $history = $record->email
            ? Donation::where('email', $record->email)->where('payment_status', 'paid')
                ->selectRaw('currency, sum(amount) as total, count(*) as gifts')->groupBy('currency')->get()
                ->map(fn ($row) => $row->currency . ' ' . number_format((float) $row->total, 2) . ' in ' . $row->gifts . ' ' . str('donation')->plural((int) $row->gifts))
                ->join(' · ')
            : '';

        return $infolist->schema([
            Infolists\Components\Section::make('Donation')
                ->columns(4)
                ->schema([
                    Infolists\Components\TextEntry::make('reference')->copyable()->fontFamily('mono'),
                    Infolists\Components\TextEntry::make('amount')->state($record->amount_formatted)->weight('bold'),
                    Infolists\Components\TextEntry::make('payment_status')->label('Status')->badge()
                        ->formatStateUsing(fn ($state) => Donation::PAYMENT_STATUSES[$state] ?? $state)
                        ->color(fn ($state) => match ($state) { 'paid' => 'success', 'failed' => 'danger', default => 'warning' }),
                    Infolists\Components\TextEntry::make('method')->label('Received by')
                        ->formatStateUsing(fn ($state) => Donation::METHODS[$state] ?? $state),
                    Infolists\Components\TextEntry::make('created_at')->label('Started')->dateTime('j M Y, H:i'),
                    Infolists\Components\TextEntry::make('paid_at')->label('Received on')->dateTime('j M Y, H:i')->placeholder('—'),
                    Infolists\Components\TextEntry::make('payment_reference')
                        ->label($record->method === 'online' ? 'Paystack reference' : 'Receipt or transfer reference')
                        ->placeholder('—')->fontFamily('mono')->columnSpan(2),
                ]),
            Infolists\Components\Section::make('Donor')
                ->columns(3)
                ->schema([
                    Infolists\Components\TextEntry::make('donor_name')->label('Name'),
                    Infolists\Components\TextEntry::make('email')->placeholder('—')->copyable(),
                    Infolists\Components\TextEntry::make('phone')->placeholder('—')->copyable(),
                    Infolists\Components\TextEntry::make('history')->label('Given in total')->state($history ?: '—')->columnSpanFull(),
                    Infolists\Components\TextEntry::make('comments')->placeholder('No comment left.')->columnSpanFull(),
                ]),
            Infolists\Components\Section::make('Notes')
                ->schema([
                    Infolists\Components\TextEntry::make('admin_notes')->hiddenLabel()->placeholder('No notes yet.'),
                ]),
        ]);
    }

    /**
     * Header actions for the view page.
     *
     * @return array<\Filament\Actions\Action|\Filament\Actions\ActionGroup>
     */
    public static function recordActions(): array
    {
        return [
            \Filament\Actions\Action::make('markReceived')
                ->authorize(fn () => static::canManage())
                ->label('Mark as received')
                ->icon('heroicon-o-banknotes')
                ->color('success')
                ->visible(fn (Donation $record) => $record->payment_status !== 'paid')
                ->modalDescription('For a donation that reached you another way, for example by bank transfer.')
                ->form([
                    Forms\Components\Select::make('method')->label('Received by')
                        ->options(collect(Donation::METHODS)->except('online'))->default('bank_transfer')->required()->native(false),
                    Forms\Components\TextInput::make('payment_reference')->label('Receipt or transfer reference (optional)'),
                ])
                ->action(function (Donation $record, array $data) {
                    $record->update([
                        'payment_status' => 'paid',
                        'paid_at' => now(),
                        'method' => $data['method'],
                        'payment_reference' => $data['payment_reference'] ?: $record->payment_reference,
                    ]);
                    Notification::make()->title('Marked as received')->success()->send();
                }),

            \Filament\Actions\Action::make('checkPayment')
                ->authorize(fn () => static::canManage())
                ->label('Check payment with Paystack')
                ->icon('heroicon-o-arrow-path')
                ->color('gray')
                ->visible(fn (Donation $record) => $record->method === 'online' && $record->payment_reference && $record->payment_status !== 'paid')
                ->action(function (Donation $record) {
                    try {
                        $record = app(Donor::class)->confirmPayment($record);
                    } catch (Throwable $e) {
                        Notification::make()->title('Paystack could not be checked')->body($e->getMessage())->danger()->send();

                        return;
                    }
                    Notification::make()->title('Status: ' . (Donation::PAYMENT_STATUSES[$record->payment_status] ?? $record->payment_status))->send();
                }),

            \Filament\Actions\ActionGroup::make([
                \Filament\Actions\Action::make('notes')
                    ->authorize(fn () => static::canManage())
                    ->label('Edit notes')
                    ->icon('heroicon-o-pencil-square')
                    ->fillForm(fn (Donation $record) => ['admin_notes' => $record->admin_notes])
                    ->form([Forms\Components\Textarea::make('admin_notes')->label('Notes (only visible to admins)')->rows(5)])
                    ->action(fn (Donation $record, array $data) => $record->update($data)),

                \Filament\Actions\DeleteAction::make(),
            ]),
        ];
    }

    public static function exportCsv($records)
    {
        $filename = 'donations-' . now()->format('Y-m-d') . '.csv';

        return response()->streamDownload(function () use ($records) {
            $out = fopen('php://output', 'w');
            fputcsv($out, ['Reference', 'Date', 'Title', 'First name', 'Last name', 'Email', 'Phone', 'Amount', 'Currency', 'Received by', 'Status', 'Received on', 'Payment reference', 'Comments', 'Notes']);
            // Donor-typed text must not run as a formula when the file is opened in a spreadsheet
            $safe = fn ($value) => is_string($value) && preg_match('/^[=+\-@\t\r]/', $value) ? "'" . $value : $value;
            foreach ($records as $d) {
                fputcsv($out, array_map($safe, [
                    $d->reference, $d->created_at?->format('Y-m-d H:i'), $d->title, $d->first_name, $d->last_name, $d->email, $d->phone,
                    $d->amount, $d->currency, Donation::METHODS[$d->method] ?? $d->method,
                    Donation::PAYMENT_STATUSES[$d->payment_status] ?? $d->payment_status,
                    $d->paid_at?->format('Y-m-d H:i'), $d->payment_reference, $d->comments, $d->admin_notes,
                ]));
            }
            fclose($out);
        }, $filename, ['Content-Type' => 'text/csv']);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListDonations::route('/'),
            'create' => Pages\CreateDonation::route('/create'),
            'view' => Pages\ViewDonation::route('/{record}'),
        ];
    }
}
