<?php

namespace App\Filament\Store\Resources\CatalogBrowsers;

use App\Filament\Store\Resources\CatalogBrowsers\Pages\ManageCatalogBrowsers;
use App\Models\ProductVariant;
use App\Models\Shop;
use App\Models\Vendor;
use App\Models\VendorListing;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Select as FormSelect;
use Filament\Forms\Components\TextInput as FormTextInput;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\BadgeColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;

class CatalogBrowserResource extends Resource
{
    protected static ?string $model = ProductVariant::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedMagnifyingGlass;

    protected static function currentVendorShopIds(): array
    {
        $customerId = Auth::id();
        if (! $customerId) {
            return [-1];
        }
        $vendorId = Vendor::where('customer_id', $customerId)->value('id');
        if (! $vendorId) {
            return [-1];
        }

        return Shop::where('vendor_id', $vendorId)->pluck('id')->all() ?: [-1];
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->with(['product.brand', 'product.category'])
            ->where('is_active', true)
            ->whereHas('product', fn (Builder $q) => $q->where('is_active', true));
    }

    public static function table(Table $table): Table
    {
        $shopIds = self::currentVendorShopIds();

        return $table
            ->columns([
                TextColumn::make('product.name')->label('Produk')->searchable()->wrap(),
                TextColumn::make('name')->label('Varian')->searchable()->wrap(),
                BadgeColumn::make('product.type')
                    ->label('Tipe')
                    ->colors(['primary' => 'device', 'success' => 'liquid', 'warning' => 'accessory'])
                    ->formatStateUsing(fn ($s) => Str::title($s)),
                TextColumn::make('product.brand.name')->label('Brand')->badge()->toggleable(),
                TextColumn::make('product.category.name')->label('Kategori')->badge()->toggleable(),
            ])
            ->filters([
                SelectFilter::make('product_type')
                    ->label('Tipe Produk')
                    ->options(['device' => 'Device', 'liquid' => 'Liquid', 'accessory' => 'Accessory'])
                    ->query(function (Builder $query, array $data): Builder {
                        $value = $data['value'] ?? null;
                        if ($value) {
                            $query->whereHas('product', fn (Builder $p) => $p->where('type', $value));
                        }

                        return $query;
                    }),

                // Brand melalui relasi product.brand
                SelectFilter::make('brand')
                    ->label('Brand')
                    ->relationship('product.brand', 'name'),

                // Kategori melalui relasi product.category
                SelectFilter::make('category')
                    ->label('Kategori')
                    ->relationship('product.category', 'name'),
            ])
            ->actions([
                Action::make('addListing')
                    ->label('Tambah ke Listing')
                    ->icon(Heroicon::OutlinedPlus)
                    ->modalHeading('Tambah Varian ke Listing Toko')
                    ->form([
                        FormSelect::make('shop_id')
                            ->label('Toko')
                            ->options(Shop::whereIn('id', $shopIds)->pluck('name', 'id'))
                            ->required()
                            ->native(false)
                            ->helperText('Pilih toko Anda yang akan menjual varian ini.'),
                        FormSelect::make('condition')
                            ->label('Kondisi')
                            ->options(['new' => 'New', 'used' => 'Used'])
                            ->default('new')
                            ->required()
                            ->helperText('Gunakan "new" untuk produk baru.'),
                        FormTextInput::make('price')
                            ->label('Harga')
                            ->numeric()->prefix('Rp')->required()
                            ->helperText('Harga jual utama (Rupiah).'),
                        FormTextInput::make('promo_price')
                            ->label('Harga Promo (opsional)')
                            ->numeric()->prefix('Rp')
                            ->helperText('Jika diisi, harus < harga utama.'),
                        DateTimePicker::make('promo_ends_at')
                            ->label('Promo Berakhir (opsional)')
                            ->seconds(false),
                        FormTextInput::make('qty_available')
                            ->label('Stok')->numeric()->default(10)->required(),
                        FormTextInput::make('min_order_qty')
                            ->label('Min. Order')->numeric()->default(1)->required(),
                        FormSelect::make('status')
                            ->label('Status')
                            ->options([
                                'active' => 'Active', 'inactive' => 'Inactive', 'out_of_stock' => 'Out of stock', 'banned' => 'Banned',
                            ])->default('active')->required(),
                    ])
                    ->action(function (ProductVariant $record, array $data): void {
                        if (! empty($data['promo_price']) && (float) $data['promo_price'] >= (float) $data['price']) {
                            Notification::make()->title('Harga promo harus lebih kecil dari harga.')->danger()->send();

                            return;
                        }
                        $exists = VendorListing::where('shop_id', $data['shop_id'])
                            ->where('product_variant_id', $record->id)
                            ->exists();
                        if ($exists) {
                            Notification::make()->title('Varian ini sudah ada di listing toko tersebut.')
                                ->warning()->send();

                            return;
                        }
                        VendorListing::create([
                            'shop_id' => (int) $data['shop_id'],
                            'product_variant_id' => $record->id,
                            'condition' => $data['condition'],
                            'price' => (int) $data['price'],
                            'promo_price' => $data['promo_price'] ?? null,
                            'promo_ends_at' => $data['promo_ends_at'] ?? null,
                            'qty_available' => (int) ($data['qty_available'] ?? 0),
                            'min_order_qty' => (int) ($data['min_order_qty'] ?? 1),
                            'status' => $data['status'] ?? 'active',
                        ]);
                        Notification::make()->title('Listing berhasil dibuat.')->success()->send();
                    }),
            ])
            ->bulkActions([])
            ->paginationPageOptions([25, 50, 100]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ManageCatalogBrowsers::route('/'),
        ];
    }
}
