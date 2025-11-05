<?php

namespace App\Filament\Store\Resources\VendorListings;

use App\Filament\Store\Resources\VendorListings\Pages\ManageVendorListings;
use App\Models\ProductVariant;
use App\Models\Shop;
use App\Models\Vendor;
use App\Models\VendorListing;
use BackedEnum;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ForceDeleteAction;
use Filament\Actions\ForceDeleteBulkAction;
use Filament\Actions\RestoreAction;
use Filament\Actions\RestoreBulkAction;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Infolists\Components\TextEntry;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\TrashedFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Illuminate\Support\Facades\Auth;

class VendorListingResource extends Resource
{
    protected static ?string $model = VendorListing::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;

    /**
     * Ambil daftar shop_id yang dimiliki vendor yang sedang login.
     */
    protected static function currentVendorShopIds(): array
    {
        $userId = Auth::id(); // diasumsikan auth customer
        if (! $userId) {
            return [-1]; // kosongkan query
        }

        /** @var Vendor|null $vendor */
        $vendor = Vendor::query()->where('customer_id', $userId)->first();
        if (! $vendor) {
            return [-1];
        }

        return Shop::query()
            ->where('vendor_id', $vendor->id)
            ->pluck('id')
            ->all() ?: [-1];
    }

    /**
     * Scope semua query resource ke shop milik vendor.
     */
    public static function getEloquentQuery(): Builder
    {
        $shopIds = self::currentVendorShopIds();

        return parent::getEloquentQuery()
            ->withoutGlobalScopes([SoftDeletingScope::class])
            ->whereIn('shop_id', $shopIds);
    }

    public static function form(Schema $schema): Schema
    {
        $shopIds = self::currentVendorShopIds();

        return $schema->components([
            // SHOP: pilih hanya dari shop vendor sendiri
            Select::make('shop_id')
                ->label('Shop')
                ->options(
                    Shop::query()
                        ->whereIn('id', $shopIds)
                        ->pluck('name', 'id')
                        ->toArray()
                )
                ->default(fn () => count($shopIds) === 1 ? $shopIds[0] : null)
                ->disabled(fn () => count($shopIds) === 1)
                ->required(),

            // VARIANT: searchable dari katalog pusat
            Select::make('product_variant_id')
                ->label('Product Variant')
                ->searchable()
                ->getSearchResultsUsing(function (string $search) {
                    return ProductVariant::query()
                        ->where('name', 'like', "%{$search}%")
                        ->orWhereHas('product', fn (Builder $q) => $q->where('name', 'like', "%{$search}%"))
                        ->limit(50)
                        ->get()
                        ->mapWithKeys(fn (ProductVariant $v) => [
                            $v->id => ($v->product?->name ? "{$v->product->name} — {$v->name}" : $v->name),
                        ])
                        ->toArray();
                })
                ->getOptionLabelUsing(function ($value) {
                    $v = ProductVariant::with('product')->find($value);

                    return $v ? ($v->product?->name ? "{$v->product->name} — {$v->name}" : $v->name) : '';
                })
                ->required(),

            // ENUM: new|used sesuai skema
            Select::make('condition')
                ->label('Condition')
                ->options([
                    'new' => 'New',
                    'used' => 'Used',
                ])
                ->default('new')
                ->required(),

            TextInput::make('price')
                ->label('Price')
                ->numeric()
                ->prefix('Rp')
                ->required(),

            TextInput::make('promo_price')
                ->label('Promo Price')
                ->numeric()
                ->prefix('Rp'),

            DateTimePicker::make('promo_ends_at')
                ->label('Promo Ends At'),

            TextInput::make('qty_available')
                ->label('Stock')
                ->numeric()
                ->default(0)
                ->required(),

            TextInput::make('min_order_qty')
                ->label('Min Order')
                ->numeric()
                ->default(1)
                ->required(),

            Select::make('status')
                ->label('Status')
                ->options([
                    'active' => 'Active',
                    'inactive' => 'Inactive',
                    'out_of_stock' => 'Out of stock',
                    'banned' => 'Banned',
                ])
                ->default('active')
                ->required(),
        ]);
    }

    public static function infolist(Schema $schema): Schema
    {
        return $schema->components([
            TextEntry::make('shop.name')->label('Shop'),
            TextEntry::make('productVariant.product.name')->label('Product'),
            TextEntry::make('productVariant.name')->label('Variant'),
            TextEntry::make('condition')->badge(),
            TextEntry::make('price')->money('IDR'),
            TextEntry::make('promo_price')->money('IDR')->placeholder('-'),
            TextEntry::make('promo_ends_at')->dateTime()->placeholder('-'),
            TextEntry::make('qty_available')->label('Stock'),
            TextEntry::make('min_order_qty')->label('Min Order'),
            TextEntry::make('status')->badge(),
            TextEntry::make('created_at')->dateTime()->placeholder('-'),
            TextEntry::make('updated_at')->dateTime()->placeholder('-'),
            TextEntry::make('deleted_at')
                ->dateTime()
                ->visible(fn (VendorListing $record): bool => $record->trashed()),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('shop.name')->label('Shop')->sortable()->toggleable(),
                TextColumn::make('product_variant.product.type')->label('Jenis')->sortable()->searchable(),
                TextColumn::make('product_variant.product.name')->label('Product')->sortable()->searchable(),
                TextColumn::make('product_variant.name')->label('Variant')->sortable()->searchable(),
                TextColumn::make('condition')->badge(),
                TextColumn::make('price')->money('IDR')->sortable(),
                TextColumn::make('promo_price')->money('IDR')->sortable(),
                TextColumn::make('promo_ends_at')->dateTime()->sortable(),
                TextColumn::make('qty_available')->label('Stock')->sortable(),
                TextColumn::make('min_order_qty')->label('Min Order')->sortable(),
                TextColumn::make('status')->badge(),
                TextColumn::make('created_at')->dateTime()->sortable()->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('updated_at')->dateTime()->sortable()->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('deleted_at')->dateTime()->sortable()->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                TrashedFilter::make(),
            ])
            ->recordActions([
                ViewAction::make(),
                EditAction::make(),
                DeleteAction::make(),
                ForceDeleteAction::make(),
                RestoreAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                    ForceDeleteBulkAction::make(),
                    RestoreBulkAction::make(),
                ]),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ManageVendorListings::route('/'),
        ];
    }

    /**
     * Pastikan binding record (view/edit/delete) juga terscope ke shop vendor.
     */
    public static function getRecordRouteBindingEloquentQuery(): Builder
    {
        $shopIds = self::currentVendorShopIds();

        return parent::getRecordRouteBindingEloquentQuery()
            ->withoutGlobalScopes([SoftDeletingScope::class])
            ->whereIn('shop_id', $shopIds);
    }
}
