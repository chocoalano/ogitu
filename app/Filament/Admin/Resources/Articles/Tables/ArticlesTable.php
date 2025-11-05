<?php

namespace App\Filament\Admin\Resources\Articles\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\RestoreBulkAction;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\DatePicker;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\TagsColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Filters\TrashedFilter;
use Filament\Tables\Table;

class ArticlesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                ImageColumn::make('cover_url')
                    ->label('Cover')
                    ->size(48)
                    ->circular()
                    ->toggleable(),

                TextColumn::make('title')
                    ->label('Title')
                    ->searchable()
                    ->sortable()
                    ->limit(60)
                    ->description(fn ($record) => $record->slug, position: 'below')
                    ->wrap(),

                TextColumn::make('category.name')
                    ->label('Kategori')
                    ->badge()
                    ->sortable()
                    ->toggleable(),

                // Tags dari kolom JSON (array)
                TagsColumn::make('tags')
                    ->label('Tags')
                    ->limit(3)
                    ->toggleable(),

                TextColumn::make('status')
                    ->label('Status')
                    ->badge()
                    ->sortable()
                    ->colors([
                        'gray' => fn ($state) => $state === 'draft',
                        'warning' => fn ($state) => $state === 'scheduled',
                        'success' => fn ($state) => $state === 'published',
                    ])
                    ->icons([
                        'heroicon-o-pencil' => fn ($state) => $state === 'draft',
                        'heroicon-o-clock' => fn ($state) => $state === 'scheduled',
                        'heroicon-o-check-badge' => fn ($state) => $state === 'published',
                    ])
                    ->toggleable(),

                IconColumn::make('noindex')
                    ->label('NI')
                    ->tooltip('noindex')
                    ->boolean()
                    ->toggleable(),

                IconColumn::make('nofollow')
                    ->label('NF')
                    ->tooltip('nofollow')
                    ->boolean()
                    ->toggleable(),

                TextColumn::make('published_at')
                    ->label('Published')
                    ->dateTime('d M Y, H:i')
                    ->since() // tampilkan “x minutes ago” sebagai tooltip
                    ->sortable()
                    ->toggleable(),

                TextColumn::make('author.name')
                    ->label('Author')
                    ->searchable()
                    ->sortable()
                    ->toggleable(),

                // Accessor dari model: read_time (menit)
                TextColumn::make('read_time')
                    ->label('Read')
                    ->suffix(' min')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])

            ->defaultSort('published_at', 'desc')

            ->filters([
                SelectFilter::make('status')
                    ->label('Status')
                    ->options([
                        'draft' => 'Draft',
                        'scheduled' => 'Terjadwal',
                        'published' => 'Terbit',
                    ]),

                SelectFilter::make('category_id')
                    ->label('Kategori Utama')
                    ->relationship('category', 'name'),

                SelectFilter::make('author_id')
                    ->label('Author')
                    ->relationship('author', 'name'),

                TernaryFilter::make('noindex')
                    ->label('Noindex'),

                TernaryFilter::make('nofollow')
                    ->label('Nofollow'),

                // Filter rentang tanggal publish
                Filter::make('published_range')
                    ->form([
                        DatePicker::make('from')->label('Dari'),
                        DatePicker::make('until')->label('Sampai'),
                    ])
                    ->query(function ($query, array $data) {
                        return $query
                            ->when($data['from'] ?? null, fn ($q, $date) => $q->whereDate('published_at', '>=', $date))
                            ->when($data['until'] ?? null, fn ($q, $date) => $q->whereDate('published_at', '<=', $date));
                    }),

                TrashedFilter::make(), // karena kita pakai SoftDeletes
            ])

            ->recordUrl(fn ($record) => null)

            ->recordActions([
                ViewAction::make()
                    ->visible(fn ($record) => method_exists($record, 'getUrlAttribute'))
                    ->url(fn ($record) => $record->url, shouldOpenInNewTab: true)
                    ->tooltip('Lihat di situs'),

                EditAction::make(),

                DeleteAction::make(),
            ])

            ->bulkActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                    RestoreBulkAction::make(),
                ]),
            ]);
    }
}
