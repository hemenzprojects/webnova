<?php

namespace App\Filament\Resources\MenuResource\RelationManagers;

use App\Models\MenuItem;
use App\Models\Page;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Forms\Get;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;

class ItemsRelationManager extends RelationManager
{
    protected static string $relationship = 'items';

    protected static ?string $title = 'Menu Items';

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Item Configuration')
                    ->schema([
                        Forms\Components\Select::make('parent_id')
                            ->label('Parent Item')
                            ->relationship('parent', 'label', function ($query) {
                                return $query->whereNull('parent_id')
                                    ->where('menu_id', $this->ownerRecord->id)
                                    ->orderBy('order');
                            })
                            ->searchable()
                            ->nullable()
                            ->helperText('Leave empty for top-level items. Select a parent to nest this item.'),

                        Forms\Components\TextInput::make('label')
                            ->required()
                            ->maxLength(255)
                            ->helperText('Display text for this menu item'),

                        Forms\Components\TextInput::make('icon')
                            ->maxLength(255)
                            ->helperText('Optional icon class (e.g., heroicon-o-home)'),

                        Forms\Components\TextInput::make('order')
                            ->numeric()
                            ->default(0)
                            ->helperText('Lower numbers appear first'),
                    ])
                    ->columns(2),

                Forms\Components\Section::make('Link Configuration')
                    ->schema([
                        Forms\Components\Select::make('type')
                            ->required()
                            ->options([
                                'page' => 'Page',
                                'custom' => 'Custom Link',
                                'category' => 'Category (Non-clickable)',
                                'news' => 'News Listing',
                                'events' => 'Events Listing',
                                'services' => 'Services Listing',
                                'members' => 'Members Listing',
                            ])
                            ->default('custom')
                            ->live()
                            ->helperText('Select the type of link'),

                        Forms\Components\Select::make('page_id')
                            ->label('Select Page')
                            ->options(function () {
                                return Page::where('is_published', true)
                                    ->orderBy('title')
                                    ->pluck('title', 'id');
                            })
                            ->searchable()
                            ->required(fn (Get $get) => $get('type') === 'page')
                            ->visible(fn (Get $get) => $get('type') === 'page')
                            ->helperText('Select a published page to link to'),

                        Forms\Components\TextInput::make('url')
                            ->label('URL')
                            ->maxLength(255)
                            ->url()
                            ->required(fn (Get $get) => $get('type') === 'custom')
                            ->visible(fn (Get $get) => $get('type') === 'custom')
                            ->helperText('Enter the full URL (e.g., https://example.com or /about)'),

                        Forms\Components\Toggle::make('open_in_new_tab')
                            ->label('Open in New Tab')
                            ->default(false)
                            ->visible(fn (Get $get) => in_array($get('type'), ['page', 'custom'])),

                        Forms\Components\TextInput::make('css_class')
                            ->label('CSS Class')
                            ->maxLength(255)
                            ->helperText('Optional custom CSS classes'),

                        Forms\Components\TextInput::make('target_id')
                            ->label('Target ID (Anchor)')
                            ->maxLength(255)
                            ->helperText('Optional anchor link target (e.g., #contact)'),

                        Forms\Components\Toggle::make('is_published')
                            ->label('Published')
                            ->default(true)
                            ->helperText('Unpublished items won\'t appear on the frontend'),
                    ])
                    ->columns(2),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('label')
            ->columns([
                Tables\Columns\TextColumn::make('label')
                    ->searchable()
                    ->description(fn (MenuItem $record): string => $record->parent ? "Parent: {$record->parent->label}" : ''),

                Tables\Columns\BadgeColumn::make('type')
                    ->colors([
                        'success' => 'page',
                        'primary' => 'custom',
                        'secondary' => 'category',
                        'info' => fn ($state) => in_array($state, ['news', 'events', 'services', 'members']),
                    ])
                    ->icons([
                        'heroicon-o-document-text' => 'page',
                        'heroicon-o-link' => 'custom',
                        'heroicon-o-folder' => 'category',
                        'heroicon-o-newspaper' => 'news',
                        'heroicon-o-calendar' => 'events',
                        'heroicon-o-briefcase' => 'services',
                        'heroicon-o-users' => 'members',
                    ]),

                Tables\Columns\TextColumn::make('url')
                    ->label('Link')
                    ->getStateUsing(fn (MenuItem $record) => $record->getUrl() ?? '-')
                    ->url(fn (MenuItem $record) => $record->getUrl())
                    ->openUrlInNewTab(fn (MenuItem $record) => $record->open_in_new_tab)
                    ->color('primary')
                    ->limit(40),

                Tables\Columns\TextColumn::make('icon')
                    ->toggleable(),

                Tables\Columns\IconColumn::make('is_published')
                    ->label('Published')
                    ->boolean(),

                Tables\Columns\TextColumn::make('order')
                    ->sortable(),

                Tables\Columns\TextColumn::make('children_count')
                    ->label('Children')
                    ->counts('children')
                    ->toggleable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('type')
                    ->options([
                        'page' => 'Page',
                        'custom' => 'Custom Link',
                        'category' => 'Category',
                        'news' => 'News',
                        'events' => 'Events',
                        'services' => 'Services',
                        'members' => 'Members',
                    ]),
                Tables\Filters\TernaryFilter::make('is_published')
                    ->label('Published Status'),
            ])
            ->headerActions([
                Tables\Actions\CreateAction::make()
                    ->mutateFormDataUsing(function (array $data): array {
                        $data['menu_id'] = $this->ownerRecord->id;
                        return $data;
                    }),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ])
            ->reorderable('order')
            ->defaultSort('order')
            ->defaultGroup('parent_id');
    }
}
