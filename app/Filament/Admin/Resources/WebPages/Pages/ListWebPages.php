<?php

namespace App\Filament\Admin\Resources\WebPages\Pages;

use App\Filament\Admin\Resources\WebPages\WebPageResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListWebPages extends ListRecords
{
    protected static string $resource = WebPageResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
