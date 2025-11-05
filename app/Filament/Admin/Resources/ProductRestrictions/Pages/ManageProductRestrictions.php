<?php

namespace App\Filament\Admin\Resources\ProductRestrictions\Pages;

use App\Filament\Admin\Resources\ProductRestrictions\ProductRestrictionResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ManageRecords;

class ManageProductRestrictions extends ManageRecords
{
    protected static string $resource = ProductRestrictionResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
