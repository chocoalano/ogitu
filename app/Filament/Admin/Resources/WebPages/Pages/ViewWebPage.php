<?php

namespace App\Filament\Admin\Resources\WebPages\Pages;

use App\Filament\Admin\Resources\WebPages\WebPageResource;
use Filament\Actions\EditAction;
use Filament\Resources\Pages\ViewRecord;

class ViewWebPage extends ViewRecord
{
    protected static string $resource = WebPageResource::class;

    protected function getHeaderActions(): array
    {
        return [
            EditAction::make(),
        ];
    }
}
