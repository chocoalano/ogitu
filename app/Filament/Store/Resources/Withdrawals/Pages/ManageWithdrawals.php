<?php

namespace App\Filament\Store\Resources\Withdrawals\Pages;

use App\Filament\Store\Resources\Withdrawals\WithdrawalResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ManageRecords;

class ManageWithdrawals extends ManageRecords
{
    protected static string $resource = WithdrawalResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
