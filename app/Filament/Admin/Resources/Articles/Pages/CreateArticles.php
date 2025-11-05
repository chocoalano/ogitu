<?php

namespace App\Filament\Admin\Resources\Articles\Pages;

use App\Filament\Admin\Resources\Articles\ArticlesResource;
use Filament\Resources\Pages\CreateRecord;

class CreateArticles extends CreateRecord
{
    protected static string $resource = ArticlesResource::class;
}
