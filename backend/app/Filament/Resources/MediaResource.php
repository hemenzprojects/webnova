<?php

namespace App\Filament\Resources;

use App\Filament\Resources\MediaResource\Pages;
use App\Models\Media;
use Filament\Forms;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class MediaResource extends Resource
{
    protected static ?string $model = Media::class;

    protected static ?string $navigationIcon = 'heroicon-o-photo';

    protected static ?string $navigationLabel = 'Media Library';

    protected static ?int $navigationSort = 10;

    public static function table(Table $table): Table
    {
        return $table
            ->contentGrid([
                'sm'  => 2,
                'md'  => 3,
                'lg'  => 4,
                'xl'  => 5,
            ])
            ->columns([
                Tables\Columns\ImageColumn::make('path')
                    ->disk('public')
                    ->label('')
                    ->height(160)
                    ->width('100%')
                    ->extraImgAttributes(['class' => 'w-full h-40 object-cover rounded-t-lg']),

                Tables\Columns\TextColumn::make('original_name')
                    ->label('Name')
                    ->searchable()
                    ->limit(28)
                    ->tooltip(fn (Media $record) => $record->original_name),

                Tables\Columns\TextColumn::make('size')
                    ->label('Size')
                    ->formatStateUsing(fn ($state) => $state
                        ? ($state >= 1048576
                            ? round($state / 1048576, 1) . ' MB'
                            : round($state / 1024, 1) . ' KB')
                        : '—')
                    ->color('gray'),

                Tables\Columns\TextColumn::make('path')
                    ->label('Path')
                    ->copyable()
                    ->copyMessage('Path copied!')
                    ->limit(40)
                    ->color('gray')
                    ->fontFamily('mono')
                    ->size(Tables\Columns\TextColumn\TextColumnSize::ExtraSmall),
            ])
            ->defaultSort('created_at', 'desc')
            ->actions([
                Tables\Actions\Action::make('view')
                    ->icon('heroicon-o-arrow-top-right-on-square')
                    ->label('Open')
                    ->url(fn (Media $record) => '/storage/' . $record->path)
                    ->openUrlInNewTab(),

                Tables\Actions\DeleteAction::make()
                    ->before(fn (Media $record) => Storage::disk($record->disk)->delete($record->path)),
            ])
            ->headerActions([
                Tables\Actions\Action::make('upload')
                    ->label('Upload Files')
                    ->icon('heroicon-o-arrow-up-tray')
                    ->form([
                        Forms\Components\FileUpload::make('files')
                            ->label('Images')
                            ->multiple()
                            ->image()
                            ->maxSize(5120)
                            ->disk('public')
                            ->saveUploadedFileUsing(function ($file): string {
                                $tenantId = tenancy()->tenant?->id;
                                $dir      = $tenantId ? "{$tenantId}/media" : 'media';
                                $filename = Str::ulid() . '.' . $file->getClientOriginalExtension();
                                $path     = $file->storeAs($dir, $filename, 'public');

                                Media::create([
                                    'filename'      => $filename,
                                    'original_name' => $file->getClientOriginalName(),
                                    'path'          => $path,
                                    'disk'          => 'public',
                                    'mime_type'     => $file->getMimeType(),
                                    'size'          => $file->getSize(),
                                ]);

                                return $path;
                            }),
                    ])
                    ->action(function (): void {
                        Notification::make()
                            ->title('Files uploaded')
                            ->success()
                            ->send();
                    }),
            ])
            ->bulkActions([
                Tables\Actions\BulkAction::make('delete')
                    ->requiresConfirmation()
                    ->icon('heroicon-o-trash')
                    ->color('danger')
                    ->action(function (\Illuminate\Database\Eloquent\Collection $records): void {
                        foreach ($records as $record) {
                            Storage::disk($record->disk)->delete($record->path);
                            $record->delete();
                        }
                    }),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListMedia::route('/'),
        ];
    }
}