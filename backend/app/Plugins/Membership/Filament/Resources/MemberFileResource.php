<?php

namespace App\Plugins\Membership\Filament\Resources;

use App\Admin\Concerns\InFunctionalArea;
use App\Filament\Resources\PageResource;
use App\Plugins\Membership\Filament\Resources\MemberFileResource\Pages;
use App\Plugins\Membership\Models\MemberResource;
use App\Plugins\Membership\Support\PrivateFiles;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Support\Number;

/**
 * Files the Association shares with its members (minutes, reports…). Unlike
 * the media library, these are private: only active members (in the member
 * portal's Members' area) and admins can download them.
 */
class MemberFileResource extends Resource
{
    use InFunctionalArea;

    protected static string $area = 'membership';

    protected static ?string $plugin = 'membership';

    protected static ?string $feature = 'accounts';

    protected static ?string $model = MemberResource::class;

    protected static ?string $slug = 'member-resources';

    protected static ?string $navigationIcon = 'heroicon-o-folder';

    protected static ?string $navigationLabel = 'Files for members';

    protected static ?string $modelLabel = 'file for members';

    protected static ?string $pluralModelLabel = 'files for members';

    protected static ?int $navigationSort = 4;

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Section::make()
                ->description('Only active members (in the member portal) and admins can open these files. Do not put members-only files in the Media Library: its files are public.')
                ->columns(2)
                ->schema([
                    Forms\Components\TextInput::make('title')->required()->maxLength(200),
                    Forms\Components\TextInput::make('category')
                        ->helperText('Optional, e.g. "Meeting minutes" or "Reports". Files are grouped by it.')
                        ->datalist(fn () => MemberResource::whereNotNull('category')->distinct()->orderBy('category')->pluck('category')->all())
                        ->maxLength(100),
                    Forms\Components\Textarea::make('description')->rows(2)->columnSpanFull(),
                    Forms\Components\FileUpload::make('file')
                        ->disk(PrivateFiles::DISK)
                        ->visibility('private')
                        ->directory(PageResource::tenantDir('membership/resources'))
                        ->storeFileNamesIn('name')
                        ->acceptedFileTypes(['application/pdf', 'application/msword', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document', 'image/jpeg', 'image/png', 'application/vnd.ms-excel', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet', 'application/vnd.ms-powerpoint', 'application/vnd.openxmlformats-officedocument.presentationml.presentation'])
                        ->maxSize(20480)
                        ->helperText('PDF, Word, Excel, PowerPoint, JPG or PNG, up to 20 MB.')
                        ->downloadable(false)
                        ->previewable(false)
                        ->required()
                        ->columnSpanFull(),
                    Forms\Components\Toggle::make('is_published')->label('Visible to members')->default(true),
                    Forms\Components\TextInput::make('order')->numeric()->default(0),
                ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('updated_at', 'desc')
            ->columns([
                Tables\Columns\TextColumn::make('title')->searchable()->sortable()
                    ->description(fn (MemberResource $record) => $record->name),
                Tables\Columns\TextColumn::make('category')->badge()->placeholder('—')->sortable(),
                Tables\Columns\TextColumn::make('size')->formatStateUsing(fn ($state) => Number::fileSize((int) $state)),
                Tables\Columns\IconColumn::make('is_published')->label('Visible')->boolean(),
                Tables\Columns\TextColumn::make('updated_at')->label('Updated')->date()->sortable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('category')
                    ->options(fn () => MemberResource::whereNotNull('category')->distinct()->orderBy('category')->pluck('category', 'category')->all()),
            ])
            ->actions([
                Tables\Actions\Action::make('download')
                    ->icon('heroicon-o-arrow-down-tray')
                    ->color('gray')
                    ->url(fn (MemberResource $record) => "/api/v1/membership/admin/member-resources/{$record->id}")
                    ->openUrlInNewTab(),
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([Tables\Actions\DeleteBulkAction::make()]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListMemberFiles::route('/'),
            'create' => Pages\CreateMemberFile::route('/create'),
            'edit' => Pages\EditMemberFile::route('/{record}/edit'),
        ];
    }
}
