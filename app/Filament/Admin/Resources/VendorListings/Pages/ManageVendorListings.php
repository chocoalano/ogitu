<?php

namespace App\Filament\Admin\Resources\VendorListings\Pages;

use App\Filament\Admin\Resources\VendorListings\VendorListingResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ManageRecords;

class ManageVendorListings extends ManageRecords
{
    protected static string $resource = VendorListingResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
