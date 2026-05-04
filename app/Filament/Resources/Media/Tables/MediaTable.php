<?php

namespace App\Filament\Resources\Media\Tables;

use Filament\Actions\Action;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Infolists\Components\ImageEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Section;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Support\Number;

class MediaTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                ImageColumn::make('preview')
                    ->label('')
                    ->getStateUsing(fn ($record) => $record->getUrl())
                    ->width(80)
                    ->height(60),
                TextColumn::make('file_name')
                    ->label('File Name')
                    ->searchable()
                    ->sortable()
                    ->limit(30),
                TextColumn::make('model_type')
                    ->label('Used By')
                    ->formatStateUsing(fn (string $state) => class_basename($state))
                    ->badge()
                    ->color('gray'),
                TextColumn::make('collection_name')
                    ->label('Collection')
                    ->badge()
                    ->color('info'),
                TextColumn::make('mime_type')
                    ->label('Type')
                    ->toggleable(),
                TextColumn::make('size')
                    ->label('Size')
                    ->formatStateUsing(fn (int $state) => Number::fileSize($state))
                    ->sortable(),
                TextColumn::make('created_at')
                    ->label('Uploaded')
                    ->dateTime()
                    ->sortable(),
            ])
            ->filters([
                SelectFilter::make('collection_name')
                    ->label('Collection')
                    ->options(function () {
                        return \App\Models\Media::query()
                            ->visibleTo(auth()->user())
                            ->distinct()
                            ->pluck('collection_name', 'collection_name')
                            ->toArray();
                    }),
                SelectFilter::make('model_type')
                    ->label('Model')
                    ->options(function () {
                        return \App\Models\Media::query()
                            ->visibleTo(auth()->user())
                            ->distinct()
                            ->pluck('model_type')
                            ->mapWithKeys(fn ($type) => [$type => class_basename($type)])
                            ->toArray();
                    }),
            ])
            ->recordActions([
                Action::make('view')
                    ->label('View')
                    ->icon('heroicon-o-eye')
                    ->modalHeading(fn ($record) => $record->file_name)
                    ->infolist([
                        ImageEntry::make('preview')
                            ->hiddenLabel()
                            ->state(fn ($record) => $record->getUrl())
                            ->height(300)
                            ->extraImgAttributes(['class' => 'rounded-lg mx-auto']),
                        TextEntry::make('url')
                            ->label('URL')
                            ->state(fn ($record) => $record->getUrl())
                            ->copyable()
                            ->copyMessage('Copied!')
                            ->copyMessageDuration(1500)
                            ->fontFamily('mono')
                            ->size('sm'),
                        Section::make('Details')
                            ->schema([
                                TextEntry::make('size')
                                    ->state(fn ($record) => Number::fileSize($record->size)),
                                TextEntry::make('mime_type')
                                    ->label('Type'),
                                TextEntry::make('created_at')
                                    ->label('Uploaded')
                                    ->dateTime(),
                            ])
                            ->columns(3)
                            ->compact(),
                    ])
                    ->modalSubmitAction(false)
                    ->modalCancelActionLabel('Close'),
                DeleteAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make()
                        ->authorizeIndividualRecords('delete'),
                ]),
            ])
            ->defaultSort('created_at', 'desc');
    }
}
