<?php

namespace App\Filament\Admin\Resources\WebPages\Schemas;

use App\Models\WebPage;
use Filament\Infolists\Components\IconEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Schema;

class WebPageInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextEntry::make('name'),
                TextEntry::make('slug'),
                TextEntry::make('path'),
                TextEntry::make('route_name')
                    ->placeholder('-'),
                TextEntry::make('position')
                    ->numeric(),
                TextEntry::make('layout')
                    ->badge(),
                TextEntry::make('schema_type')
                    ->badge(),
                TextEntry::make('seo_title')
                    ->placeholder('-'),
                TextEntry::make('meta_description')
                    ->placeholder('-'),
                IconEntry::make('noindex')
                    ->boolean(),
                IconEntry::make('nofollow')
                    ->boolean(),
                IconEntry::make('is_active')
                    ->boolean(),
                TextEntry::make('excerpt')
                    ->placeholder('-')
                    ->columnSpanFull(),
                TextEntry::make('created_at')
                    ->dateTime()
                    ->placeholder('-'),
                TextEntry::make('updated_at')
                    ->dateTime()
                    ->placeholder('-'),
                TextEntry::make('deleted_at')
                    ->dateTime()
                    ->visible(fn (WebPage $record): bool => $record->trashed()),
            ]);
    }
}
