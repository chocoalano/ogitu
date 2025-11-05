<?php

namespace App\Filament\Admin\Resources\LedgerEntries\Pages;

use App\Filament\Admin\Resources\LedgerEntries\LedgerEntryResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ManageRecords;

class ManageLedgerEntries extends ManageRecords
{
    protected static string $resource = LedgerEntryResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
