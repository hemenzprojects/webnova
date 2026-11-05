<?php

namespace App\Plugins\Membership\Filament\Resources\MemberAccountResource\RelationManagers;

use App\Filament\Resources\PageResource;
use App\Plugins\Membership\Filament\Resources\MemberAccountResource;
use App\Plugins\Membership\Models\MemberDocument;
use App\Plugins\Membership\Support\PrivateFiles;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Number;

/**
 * A member's documents (My documents in the portal): theirs, copied from
 * their application, or added by an admin. Private files.
 */
class DocumentsRelationManager extends RelationManager
{
    protected static string $relationship = 'documents';

    protected static ?string $title = 'Documents';

    public static function canViewForRecord(Model $ownerRecord, string $pageClass): bool
    {
        return MemberAccountResource::canAccessArea();
    }

    public function isReadOnly(): bool
    {
        return ! MemberAccountResource::canManage();
    }

    public function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\TextInput::make('title')->required()->maxLength(150),
            Forms\Components\Select::make('category')->options(MemberDocument::CATEGORIES)->required()->native(false),
            Forms\Components\FileUpload::make('file')
                ->disk(PrivateFiles::DISK)
                ->visibility('private')
                ->directory(fn () => PageResource::tenantDir('membership/members/' . $this->getOwnerRecord()->getKey())())
                ->storeFileNamesIn('name')
                ->acceptedFileTypes(['application/pdf', 'application/msword', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document', 'image/jpeg', 'image/png'])
                ->maxSize(PrivateFiles::MAX_KB)
                ->helperText(PrivateFiles::HINT)
                ->downloadable(false)
                ->previewable(false)
                ->required()
                ->columnSpanFull(),
        ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('title')
            ->defaultSort('created_at', 'desc')
            ->columns([
                Tables\Columns\TextColumn::make('title')->description(fn (MemberDocument $record) => $record->name),
                Tables\Columns\TextColumn::make('category')->badge()->formatStateUsing(fn ($state) => MemberDocument::CATEGORIES[$state] ?? $state),
                Tables\Columns\TextColumn::make('source')->formatStateUsing(fn ($state) => MemberDocument::SOURCES[$state] ?? $state)->color('gray'),
                Tables\Columns\TextColumn::make('size')->formatStateUsing(fn ($state) => Number::fileSize((int) $state)),
                Tables\Columns\TextColumn::make('created_at')->label('Added')->date(),
            ])
            ->headerActions([
                Tables\Actions\CreateAction::make()
                    ->label('Add document')
                    ->mutateFormDataUsing(fn (array $data) => $data + ['source' => 'admin']),
            ])
            ->actions([
                Tables\Actions\Action::make('download')
                    ->icon('heroicon-o-arrow-down-tray')
                    ->color('gray')
                    ->url(fn (MemberDocument $record) => "/api/v1/membership/admin/member-documents/{$record->id}")
                    ->openUrlInNewTab(),
                Tables\Actions\DeleteAction::make(),
            ]);
    }
}
