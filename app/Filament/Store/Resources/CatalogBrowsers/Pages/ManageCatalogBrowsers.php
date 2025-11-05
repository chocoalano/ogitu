<?php

namespace App\Filament\Store\Resources\CatalogBrowsers\Pages;

use App\Filament\Store\Resources\CatalogBrowsers\CatalogBrowserResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ManageRecords;

class ManageCatalogBrowsers extends ManageRecords
{
    protected static string $resource = CatalogBrowserResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
