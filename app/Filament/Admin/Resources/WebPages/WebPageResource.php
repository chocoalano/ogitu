<?php

namespace App\Filament\Admin\Resources\WebPages;

use App\Filament\Admin\Resources\WebPages\Pages\CreateWebPage;
use App\Filament\Admin\Resources\WebPages\Pages\EditWebPage;
use App\Filament\Admin\Resources\WebPages\Pages\ListWebPages;
use App\Filament\Admin\Resources\WebPages\Pages\ViewWebPage;
use App\Filament\Admin\Resources\WebPages\Schemas\WebPageForm;
use App\Filament\Admin\Resources\WebPages\Schemas\WebPageInfolist;
use App\Filament\Admin\Resources\WebPages\Tables\WebPagesTable;
use App\Models\WebPage;
use UnitEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class WebPageResource extends Resource
{
    protected static ?string $model = WebPage::class;

    protected static string|UnitEnum|null $navigationGroup = 'Artikel & Halaman Website';

    public static function form(Schema $schema): Schema
    {
        return WebPageForm::configure($schema);
    }

    public static function infolist(Schema $schema): Schema
    {
        return WebPageInfolist::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return WebPagesTable::configure($table);
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
            'index' => ListWebPages::route('/'),
            'create' => CreateWebPage::route('/create'),
            'view' => ViewWebPage::route('/{record}'),
            'edit' => EditWebPage::route('/{record}/edit'),
        ];
    }

    public static function getRecordRouteBindingEloquentQuery(): Builder
    {
        return parent::getRecordRouteBindingEloquentQuery()
            ->withoutGlobalScopes([
                SoftDeletingScope::class,
            ]);
    }
}
