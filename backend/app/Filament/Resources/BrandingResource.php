<?php

namespace App\Filament\Resources;

use App\Filament\Resources\BrandingResource\Pages;
use App\Filament\Resources\BrandingResource\RelationManagers;
use App\Models\Branding;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class BrandingResource extends Resource
{
    protected static ?string $model = Branding::class;

    protected static ?string $navigationIcon = 'heroicon-o-paint-brush';

    protected static ?string $navigationLabel = 'Branding & Settings';

    protected static ?string $modelLabel = 'Branding';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                // Site Information Section
                Forms\Components\Section::make('Site Information')
                    ->description('Basic information about your website')
                    ->schema([
                        Forms\Components\TextInput::make('site_name')
                            ->required()
                            ->maxLength(255)
                            ->default('WEBNOVA')
                            ->placeholder('Your Organization Name'),
                        Forms\Components\TextInput::make('site_tagline')
                            ->maxLength(255)
                            ->placeholder('A short tagline or slogan'),
                        Forms\Components\Textarea::make('site_description')
                            ->rows(3)
                            ->placeholder('Brief description of your organization')
                            ->columnSpanFull(),
                    ])
                    ->columns(2)
                    ->collapsible(),

                // Logos Section
                Forms\Components\Section::make('Logos & Branding Assets')
                    ->description('Upload your organization logos and favicon')
                    ->schema([
                        Forms\Components\FileUpload::make('logo')
                            ->label('Main Logo')
                            ->image()
                            ->disk('public')
                            ->directory('branding')
                            ->imageEditor()
                            ->maxSize(2048)
                            ->helperText('Primary logo for light backgrounds (PNG with transparency recommended)'),
                        Forms\Components\FileUpload::make('logo_light')
                            ->label('Light Logo (Optional)')
                            ->image()
                            ->disk('public')
                            ->directory('branding')
                            ->imageEditor()
                            ->maxSize(2048)
                            ->helperText('Alternative logo for dark backgrounds'),
                        Forms\Components\FileUpload::make('favicon')
                            ->label('Favicon')
                            ->image()
                            ->disk('public')
                            ->directory('branding')
                            ->acceptedFileTypes(['image/x-icon', 'image/png', 'image/svg+xml'])
                            ->maxSize(512)
                            ->helperText('Small icon that appears in browser tabs (16x16 or 32x32 px)'),
                    ])
                    ->columns(3)
                    ->collapsible(),

                // Colors Section
                Forms\Components\Section::make('Brand Colors')
                    ->description('Customize your brand color palette')
                    ->schema([
                        Forms\Components\Fieldset::make('Primary Colors')
                            ->schema([
                                Forms\Components\ColorPicker::make('primary_color')
                                    ->label('Primary')
                                    ->required()
                                    ->default('#0A1E3E'),
                                Forms\Components\ColorPicker::make('primary_light')
                                    ->label('Primary Light')
                                    ->required()
                                    ->default('#1A3A5C'),
                                Forms\Components\ColorPicker::make('primary_dark')
                                    ->label('Primary Dark')
                                    ->required()
                                    ->default('#050F1F'),
                            ])
                            ->columns(3),

                        Forms\Components\Fieldset::make('Accent Colors')
                            ->schema([
                                Forms\Components\ColorPicker::make('accent_color')
                                    ->label('Accent')
                                    ->required()
                                    ->default('#00D9FF'),
                                Forms\Components\ColorPicker::make('accent_light')
                                    ->label('Accent Light')
                                    ->required()
                                    ->default('#33E3FF'),
                                Forms\Components\ColorPicker::make('accent_dark')
                                    ->label('Accent Dark')
                                    ->required()
                                    ->default('#00A8CC'),
                            ])
                            ->columns(3),

                        Forms\Components\Fieldset::make('General Colors')
                            ->schema([
                                Forms\Components\ColorPicker::make('text_color')
                                    ->label('Text Color')
                                    ->required()
                                    ->default('#1F2937'),
                                Forms\Components\ColorPicker::make('background_color')
                                    ->label('Background Color')
                                    ->required()
                                    ->default('#FFFFFF'),
                            ])
                            ->columns(2),
                    ])
                    ->collapsible(),

                // Contact Information Section
                Forms\Components\Section::make('Contact Information')
                    ->description('Your organization contact details')
                    ->schema([
                        Forms\Components\TextInput::make('contact_email')
                            ->email()
                            ->maxLength(255)
                            ->placeholder('info@example.com'),
                        Forms\Components\TextInput::make('contact_phone')
                            ->tel()
                            ->maxLength(255)
                            ->placeholder('+233 XX XXX XXXX'),
                        Forms\Components\Textarea::make('contact_address')
                            ->rows(3)
                            ->placeholder('Your physical address')
                            ->columnSpanFull(),
                    ])
                    ->columns(2)
                    ->collapsible(),

                // Social Media Section
                Forms\Components\Section::make('Social Media Links')
                    ->description('Your social media profiles')
                    ->schema([
                        Forms\Components\TextInput::make('facebook_url')
                            ->label('Facebook')
                            ->url()
                            ->maxLength(255)
                            ->placeholder('https://facebook.com/yourpage')
                            ->prefix('facebook.com/'),
                        Forms\Components\TextInput::make('twitter_url')
                            ->label('Twitter/X')
                            ->url()
                            ->maxLength(255)
                            ->placeholder('https://twitter.com/yourhandle')
                            ->prefix('twitter.com/'),
                        Forms\Components\TextInput::make('linkedin_url')
                            ->label('LinkedIn')
                            ->url()
                            ->maxLength(255)
                            ->placeholder('https://linkedin.com/company/yourcompany')
                            ->prefix('linkedin.com/'),
                        Forms\Components\TextInput::make('instagram_url')
                            ->label('Instagram')
                            ->url()
                            ->maxLength(255)
                            ->placeholder('https://instagram.com/yourhandle')
                            ->prefix('instagram.com/'),
                        Forms\Components\TextInput::make('youtube_url')
                            ->label('YouTube')
                            ->url()
                            ->maxLength(255)
                            ->placeholder('https://youtube.com/c/yourchannel')
                            ->prefix('youtube.com/'),
                    ])
                    ->columns(2)
                    ->collapsible(),

                // SEO Section
                Forms\Components\Section::make('SEO Settings')
                    ->description('Search engine optimization settings')
                    ->schema([
                        Forms\Components\Textarea::make('meta_description')
                            ->rows(3)
                            ->maxLength(160)
                            ->placeholder('A brief description for search engines (recommended: 150-160 characters)')
                            ->helperText('This appears in search engine results')
                            ->columnSpanFull(),
                        Forms\Components\TagsInput::make('meta_keywords')
                            ->placeholder('Add keywords')
                            ->helperText('Press Enter after each keyword')
                            ->columnSpanFull(),
                    ])
                    ->collapsible(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('site_name')
                    ->label('Site Name')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\ImageColumn::make('logo')
                    ->label('Logo')
                    ->disk('public'),
                Tables\Columns\ColorColumn::make('primary_color')
                    ->label('Primary Color'),
                Tables\Columns\ColorColumn::make('accent_color')
                    ->label('Accent Color'),
                Tables\Columns\TextColumn::make('contact_email')
                    ->label('Contact Email')
                    ->searchable(),
                Tables\Columns\TextColumn::make('updated_at')
                    ->label('Last Updated')
                    ->dateTime()
                    ->sortable(),
            ])
            ->filters([
                //
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
            ])
            ->bulkActions([
                // Remove bulk delete - this is settings
            ]);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListBrandings::route('/'),
            'create' => Pages\CreateBranding::route('/create'),
            'edit' => Pages\EditBranding::route('/{record}/edit'),
        ];
    }

    // Only allow one branding record
    public static function canCreate(): bool
    {
        return Branding::count() === 0;
    }
}
