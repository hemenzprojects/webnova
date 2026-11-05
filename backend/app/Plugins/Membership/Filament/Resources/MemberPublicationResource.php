<?php

namespace App\Plugins\Membership\Filament\Resources;

use App\Admin\Concerns\InFunctionalArea;
use App\Filament\Resources\PageResource;
use App\Plugins\Membership\Filament\Resources\MemberPublicationResource\Pages;
use App\Plugins\Membership\Models\MemberAccount;
use App\Plugins\Membership\Models\MemberPublication;
use App\Plugins\Membership\Support\Publications;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

/**
 * Member updates (Membership): the editorial team's review queue of
 * publications, milestones and news members submit in the portal. Nothing
 * appears on the website until it is published here. Its own permission, so
 * only the editorial team's role need see it.
 */
class MemberPublicationResource extends Resource
{
    use InFunctionalArea;

    protected static string $area = 'membership';

    protected static ?string $plugin = 'membership';

    protected static ?string $feature = 'publications';

    protected static ?string $model = MemberPublication::class;

    protected static ?string $slug = 'member-updates';

    protected static ?string $navigationIcon = 'heroicon-o-newspaper';

    protected static ?string $navigationLabel = 'Member updates';

    protected static ?string $modelLabel = 'member update';

    protected static ?int $navigationSort = 5;

    public static function getNavigationBadge(): ?string
    {
        $pending = MemberPublication::where('status', 'pending')->count();

        return $pending ? (string) $pending : null;
    }

    public static function getNavigationBadgeTooltip(): ?string
    {
        return 'Waiting for review';
    }

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Section::make()
                ->columns(2)
                ->schema([
                    Forms\Components\Placeholder::make('state')
                        ->label('Status')
                        ->content(fn (?MemberPublication $record) => $record
                            ? (MemberPublication::STATUSES[$record->status] ?? $record->status)
                                . ($record->status === 'rejected' && $record->review_note ? ". Reason given: {$record->review_note}" : '')
                                . ($record->reviewed_at ? ' (' . $record->reviewed_at->format('j M Y') . ')' : '')
                            : 'Published when you save, with the Publish button')
                        ->columnSpanFull(),
                    Forms\Components\Select::make('member_account_id')
                        ->label('Member')
                        ->relationship('memberAccount', 'name')
                        ->getOptionLabelFromRecordUsing(fn (MemberAccount $a) => "{$a->name} ({$a->member_number})")
                        ->searchable(['name', 'member_number', 'email'])
                        ->helperText('Who it is about. Members usually submit their own in the portal.'),
                    Forms\Components\Select::make('kind')->label('Type')->options(MemberPublication::KINDS)->required()->native(false),
                    Forms\Components\TextInput::make('title')->required()->maxLength(200)->columnSpanFull(),
                    Forms\Components\Textarea::make('summary')->rows(3)->maxLength(600)->columnSpanFull(),
                    Forms\Components\TextInput::make('link')
                        ->label('Link')
                        ->url()
                        ->maxLength(500)
                        ->helperText('Where the publication is hosted. Check it opens the right page before publishing.')
                        ->suffixAction(fn (?string $state) => $state ? Forms\Components\Actions\Action::make('open')->icon('heroicon-o-arrow-top-right-on-square')->url($state, true) : null)
                        ->columnSpanFull(),
                    Forms\Components\FileUpload::make('image')
                        ->label('Cover image')
                        ->image()
                        ->disk('public')
                        ->directory(PageResource::tenantDir('membership/updates'))
                        ->maxSize(MemberPublication::IMAGE_MAX_KB)
                        ->acceptedFileTypes(['image/jpeg', 'image/png', 'image/webp'])
                        ->imageEditor(),
                    Forms\Components\Toggle::make('is_featured')
                        ->label('Feature it')
                        ->helperText('Shown first in the Member updates block (when it is set to show featured first).'),
                ]),
        ])->disabled(! static::canManage());
    }

    public static function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn ($query) => $query->with('memberAccount'))
            ->defaultSort('created_at', 'desc')
            ->columns([
                Tables\Columns\ImageColumn::make('image')->label('')->disk('public')->height(48)->width(64)->extraImgAttributes(['class' => 'object-cover rounded']),
                Tables\Columns\TextColumn::make('title')->searchable()->wrap()->limit(80)
                    ->description(fn (MemberPublication $r) => MemberPublication::KINDS[$r->kind] ?? $r->kind),
                Tables\Columns\TextColumn::make('memberAccount.name')->label('Member')->placeholder('—')
                    ->description(fn (MemberPublication $r) => $r->memberAccount?->member_number)
                    ->searchable(),
                Tables\Columns\TextColumn::make('status')->badge()
                    ->formatStateUsing(fn ($state) => MemberPublication::STATUSES[$state] ?? $state)
                    ->color(fn ($state) => match ($state) { 'approved' => 'success', 'rejected' => 'danger', default => 'warning' }),
                Tables\Columns\IconColumn::make('is_featured')->label('Featured')->boolean()->toggleable(),
                Tables\Columns\TextColumn::make('created_at')->label('Submitted')->date('j M Y')->sortable(),
                Tables\Columns\TextColumn::make('published_at')->label('Published')->date('j M Y')->placeholder('—')->sortable()->toggleable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('kind')->label('Type')->options(MemberPublication::KINDS),
            ])
            ->actions([
                ...static::reviewActions(Tables\Actions\Action::class),
                Tables\Actions\EditAction::make()->label('Open'),
            ])
            ->emptyStateHeading('No member updates')
            ->emptyStateDescription('Members submit publications, milestones and news in the member portal (My submissions).');
    }

    /**
     * Publish, don't publish, unpublish: in the table and on the update's page.
     *
     * @param  class-string  $class  Tables\Actions\Action or Filament\Actions\Action
     */
    public static function reviewActions(string $class): array
    {
        return [
            $class::make('publish')
                ->label('Publish')
                ->icon('heroicon-o-check-circle')
                ->color('success')
                ->authorize(fn () => static::canManage())
                ->visible(fn (MemberPublication $record) => $record->status !== 'approved')
                ->requiresConfirmation()
                ->modalDescription('It appears on the website (Member updates) straight away.')
                ->form([Forms\Components\Toggle::make('notify')->label('Email the member')->default(true)])
                ->action(function (MemberPublication $record, array $data) {
                    app(Publications::class)->publish($record, (bool) ($data['notify'] ?? true));
                    Notification::make()->title('Published on the website')->success()->send();
                }),
            $class::make('reject')
                ->label("Don't publish")
                ->icon('heroicon-o-x-circle')
                ->color('danger')
                ->authorize(fn () => static::canManage())
                ->visible(fn (MemberPublication $record) => $record->status === 'pending')
                ->modalDescription('The member can change it and send it again.')
                ->form([
                    Forms\Components\Textarea::make('reason')->label('Reason (shown to the member)')->rows(3)->maxLength(1000),
                    Forms\Components\Toggle::make('notify')->label('Email the member')->default(true),
                ])
                ->action(function (MemberPublication $record, array $data) {
                    app(Publications::class)->reject($record, $data['reason'] ?? null, (bool) ($data['notify'] ?? true));
                    Notification::make()->title('Not published')->send();
                }),
            $class::make('unpublish')
                ->label('Take off the website')
                ->icon('heroicon-o-eye-slash')
                ->color('gray')
                ->authorize(fn () => static::canManage())
                ->visible(fn (MemberPublication $record) => $record->status === 'approved')
                ->requiresConfirmation()
                ->modalDescription('It goes back to "Waiting for review"; the member is not emailed.')
                ->action(function (MemberPublication $record) {
                    $record->update(['status' => 'pending', 'published_at' => null, 'is_featured' => false]);
                    Notification::make()->title('Taken off the website')->send();
                }),
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListMemberPublications::route('/'),
            'create' => Pages\CreateMemberPublication::route('/create'),
            // /admin/member-updates/{id}: the link in the "new submission" email
            'edit' => Pages\EditMemberPublication::route('/{record}'),
        ];
    }
}
