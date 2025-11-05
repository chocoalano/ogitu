<?php

namespace App\Filament\Admin\Resources\ProductRelations\Pages;

use App\Filament\Admin\Resources\ProductRelations\ProductRelationResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ManageRecords;

class ManageProductRelations extends ManageRecords
{
    protected static string $resource = ProductRelationResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
