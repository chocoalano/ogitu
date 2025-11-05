<?php

namespace App\Filament\Admin\Resources\Products\Pages;

use App\Filament\Admin\Resources\Products\ProductResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ManageRecords;
use Illuminate\Support\Str;

class ManageProducts extends ManageRecords
{
    protected static string $resource = ProductResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()
                ->using(function (array $data): \Illuminate\Database\Eloquent\Model {
                    $product = ProductResource::getModel()::create($data);
                    if (isset($data['images'])) {
                        $uploadedImages = $data['images'];
                        // Add new images
                        foreach ($uploadedImages as $index => $imagePath) {
                            \App\Models\Medium::create([
                                'owner_id' => $product->id,
                                'owner_type' => 'product',
                                'url' => 'storage/'.$imagePath,
                                'position' => $index + 1,
                                'alt' => Str::slug($product->name),
                            ]);
                        }
                    }

                    return $product;
                }),
        ];
    }
}
