<?php

namespace App\Filament\Admin\Resources\WalletAccounts\Pages;

use App\Filament\Admin\Resources\WalletAccounts\WalletAccountResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ManageRecords;

class ManageWalletAccounts extends ManageRecords
{
    protected static string $resource = WalletAccountResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
