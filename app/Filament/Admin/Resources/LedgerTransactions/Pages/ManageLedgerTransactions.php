<?php

namespace App\Filament\Admin\Resources\LedgerTransactions\Pages;

use App\Filament\Admin\Resources\LedgerTransactions\LedgerTransactionResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ManageRecords;

class ManageLedgerTransactions extends ManageRecords
{
    protected static string $resource = LedgerTransactionResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
