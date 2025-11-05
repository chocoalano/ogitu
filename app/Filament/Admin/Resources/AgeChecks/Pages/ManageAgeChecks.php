<?php

namespace App\Filament\Admin\Resources\AgeChecks\Pages;

use App\Filament\Admin\Resources\AgeChecks\AgeCheckResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ManageRecords;

class ManageAgeChecks extends ManageRecords
{
    protected static string $resource = AgeCheckResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
