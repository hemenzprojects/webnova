<?php

namespace App\Filament\Resources;

use App\Admin\Concerns\InFunctionalArea;
use App\Filament\Resources\TenantResource\Pages;
use App\Filament\Resources\TenantResource\RelationManagers;
use App\Models\Tenant;
use App\Services\DomainCheck;
use App\Services\StorageQuota;
use App\Services\ThemeService;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

/**
 * Central admin only: client sites.
 */
class TenantResource extends Resource
{
    use InFunctionalArea;

    protected static string $area = 'platform';

    protected static ?string $model = Tenant::class;

    protected static ?string $navigationIcon = 'heroicon-o-building-office-2';

    protected static ?string $navigationLabel = 'Tenants';

    protected static ?int $navigationSort = 1;

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Section::make('Site')
                ->columns(2)
                ->schema([
                    Forms\Components\TextInput::make('id')
                        ->label('Site ID')
                        ->helperText('Lowercase letters, numbers and dashes. Its database is named customer_{id}.')
                        ->required()
                        ->regex('/^[a-z0-9-]+$/')
                        ->unique(ignoreRecord: true)
                        ->disabledOn('edit'),
                    Forms\Components\TextInput::make('name')
                        ->required()
                        ->maxLength(255),
                    static::domainField('domain')
                        ->visibleOn('create')
                        ->dehydrated(false),
                    static::domainConfirmField('domain')
                        ->hiddenOn('edit')
                        ->columnSpanFull(),
                    Forms\Components\Toggle::make('add_www')
                        ->label('Also add www.')
                        ->helperText('Serve the site on www.{domain} as well, with its own certificate.')
                        ->inline(false)
                        ->visibleOn('create')
                        ->dehydrated(false),
                    Forms\Components\TextInput::make('storage_limit_mb')
                        ->label('Storage limit')
                        ->suffix('MB')
                        ->numeric()
                        ->integer()
                        ->minValue(1)
                        ->placeholder((string) config('quota.default_limit_mb'))
                        ->helperText(fn () => 'Uploads are refused once the site reaches this. Leave empty for the default. '.static::allocationSummary()),
                ]),

            Forms\Components\Section::make('First administrator')
                ->description('The person who will manage this site. They can add more users under System Administration → Users.')
                ->columns(2)
                ->visibleOn('create')
                ->schema([
                    Forms\Components\TextInput::make('admin_email')
                        ->label('Email')
                        ->email()
                        ->required()
                        ->dehydrated(false),
                    Forms\Components\TextInput::make('admin_password')
                        ->label('Password')
                        ->password()
                        ->revealable()
                        ->minLength(8)
                        ->required()
                        ->dehydrated(false),
                ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('id')->label('Site ID')->searchable(),
                Tables\Columns\TextColumn::make('name')->searchable(),
                Tables\Columns\TextColumn::make('domains.domain')->label('Domain')->badge(),
                Tables\Columns\TextColumn::make('theme_slug')
                    ->label('Theme')
                    ->formatStateUsing(fn (?string $state) => app(ThemeService::class)->get($state ?? 'default')['name'] ?? $state),
                Tables\Columns\TextColumn::make('storage')
                    ->label('Storage')
                    ->state(function (Tenant $record): string {
                        $quota = app(StorageQuota::class);

                        return StorageQuota::format($quota->cachedUsedBytes($record)).' of '.StorageQuota::format($quota->limitBytes($record));
                    })
                    ->description(function (Tenant $record): string {
                        $usage = app(StorageQuota::class)->cachedUsage($record);

                        return 'Files '.StorageQuota::format($usage['files']).' · Database '.StorageQuota::format($usage['database']);
                    })
                    ->badge()
                    ->color(function (Tenant $record): string {
                        $quota = app(StorageQuota::class);
                        $percent = $quota->percentUsed($record, $quota->cachedUsedBytes($record));

                        return match (true) {
                            $percent >= 100 => 'danger',
                            $percent >= config('quota.warn_at') => 'warning',
                            default => 'success',
                        };
                    }),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                static::configureDelete(Tables\Actions\DeleteAction::make()),
            ]);
    }

    /**
     * A domain as typed (with or without https://, www. kept, trailing slash
     * or path dropped) is stored as a bare lower-case hostname. Adding one
     * requests its certificate, so it must be spelled right.
     */
    public static function domainField(string $name): Forms\Components\TextInput
    {
        $describe = fn (?string $state) => ($check = static::checkDomain($state)) ? DomainCheck::describe($check) : null;

        return Forms\Components\TextInput::make($name)
            ->label('Domain')
            ->placeholder('school.edu.gh')
            ->live(onBlur: true)
            ->hint(fn (?string $state) => $describe($state)[0] ?? null)
            ->hintColor(fn (?string $state) => $describe($state)[1] ?? null)
            ->hintIcon(fn (?string $state) => $describe($state)[2] ?? null)
            ->helperText(fn (?string $state) => $describe($state)[3]
                ?? 'Just the domain, e.g. school.edu.gh. It is looked up when you leave the field, and a certificate is requested as soon as it is saved.')
            ->required()
            ->maxLength(253)
            ->dehydrateStateUsing(fn (?string $state) => static::normalizeDomain($state))
            ->rules([
                fn () => function (string $attribute, $value, \Closure $fail) {
                    $domain = static::normalizeDomain($value);
                    if (! static::isValidDomain($domain)) {
                        $fail("\"{$domain}\" is not a valid domain.");
                    } elseif (in_array($domain, config('tenancy.central_domains', []), true)) {
                        $fail('That is the platform\'s own domain.');
                    } elseif (\Illuminate\Support\Facades\DB::table('domains')->where('domain', $domain)->exists()) {
                        $fail("{$domain} is already used by a site.");
                    }
                },
            ]);
    }

    /**
     * Shown only when the domain does not exist in DNS: saving it anyway
     * needs a deliberate tick.
     */
    public static function domainConfirmField(string $domainField): Forms\Components\Checkbox
    {
        return Forms\Components\Checkbox::make("{$domainField}_confirmed")
            ->label('I have checked the spelling. Save this domain anyway.')
            ->visible(fn (Forms\Get $get) => (static::checkDomain($get($domainField))['state'] ?? null) === DomainCheck::MISSING)
            ->accepted()
            ->validationMessages(['accepted' => 'This domain was not found. Check the spelling, then tick this box to save it anyway.'])
            ->dehydrated(false);
    }

    /** DNS lookup of a typed domain; null while it is empty or malformed */
    public static function checkDomain(?string $state): ?array
    {
        $domain = static::normalizeDomain($state);

        return static::isValidDomain($domain) ? app(DomainCheck::class)->check($domain) : null;
    }

    public static function isValidDomain(string $domain): bool
    {
        return (bool) preg_match('/^(?=.{1,253}$)([a-z0-9]([a-z0-9-]{0,61}[a-z0-9])?\.)+[a-z]{2,63}$/', $domain);
    }

    public static function normalizeDomain(?string $domain): string
    {
        $domain = strtolower(trim((string) $domain));
        $domain = preg_replace('#^[a-z]+://#', '', $domain);

        return rtrim(strtok($domain, '/?#:') ?: '', '.');
    }

    /**
     * Deleting a site drops its database and files, so the admin must type
     * the site ID and their password.
     *
     * @template T of \Filament\Actions\DeleteAction|\Filament\Tables\Actions\DeleteAction
     *
     * @param  T  $action
     * @return T
     */
    public static function configureDelete($action)
    {
        return $action
            ->label('Delete site')
            ->modalHeading(fn (Tenant $record) => "Delete {$record->name}?")
            ->modalDescription(fn (Tenant $record) => 'This deletes the site\'s database (pages, users, settings, payments), '
                .'its uploaded files and theme backups, and its statistics. '
                .$record->domains->pluck('domain')->join(', ').' will stop working. This cannot be undone.')
            ->form([
                Forms\Components\TextInput::make('confirm_id')
                    ->label(fn (Tenant $record) => "Type {$record->id} to confirm")
                    ->required()
                    ->in(fn (Tenant $record) => [$record->id])
                    ->validationMessages(['in' => 'The site ID does not match.']),
                Forms\Components\TextInput::make('password')
                    ->label('Your password')
                    ->password()
                    ->required()
                    ->currentPassword()
                    ->validationMessages(['current_password' => 'The password is incorrect.']),
            ])
            ->modalSubmitActionLabel('Delete permanently')
            ->successNotificationTitle('Site deleted');
    }

    /**
     * How much of the server's storage the sites' limits add up to.
     */
    public static function allocationSummary(): string
    {
        $quota = app(StorageQuota::class);
        $allocated = $quota->allocatedBytes();
        $capacity = $quota->capacityBytes();

        if (! $capacity) {
            return StorageQuota::format($allocated).' allocated across all sites.';
        }

        $summary = StorageQuota::format($allocated).' of '.StorageQuota::format($capacity).' server storage allocated across all sites.';

        return $allocated > $capacity
            ? $summary.' That is '.StorageQuota::format($allocated - $capacity).' more than the server has.'
            : $summary;
    }

    public static function getRelations(): array
    {
        return [RelationManagers\DomainsRelationManager::class];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListTenants::route('/'),
            'create' => Pages\CreateTenant::route('/create'),
            'edit' => Pages\EditTenant::route('/{record}/edit'),
        ];
    }
}
