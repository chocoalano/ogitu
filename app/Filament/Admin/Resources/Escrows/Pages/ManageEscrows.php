<?php

namespace App\Filament\Admin\Resources\Escrows\Pages;

use App\Filament\Admin\Resources\Escrows\EscrowResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ManageRecords;

class ManageEscrows extends ManageRecords
{
    protected static string $resource = EscrowResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
