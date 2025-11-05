<?php

namespace App\Filament\Admin\Resources\VendorListings;

use App\Filament\Admin\Resources\VendorListings\Pages\ManageVendorListings;
use App\Models\Brand;
use App\Models\Category;
use App\Models\ProductVariant;
use App\Models\Shop;
use App\Models\VendorListing;
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
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TrashedFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use UnitEnum;

class VendorListingResource extends Resource
{
    protected static ?string $model = VendorListing::class;

    protected static string|UnitEnum|null $navigationGroup = 'Inventory Movement Resource';

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Select::make('shop_id')
                ->label('Toko')
                ->options(fn () => Shop::query()->orderBy('name')->pluck('name', 'id')->toArray())
                ->searchable()
                ->required()
                ->helperText('Pilih toko pemilik listing ini.'),

            Select::make('product_variant_id')
                ->label('Varian Produk')
                ->searchable()
                ->getSearchResultsUsing(function (string $search) {
                    return ProductVariant::query()
                        ->with('product')
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
                ->required()
                ->helperText('Cari varian dari katalog pusat. Admin dapat memilih varian manapun.'),

            Select::make('condition')
                ->label('Kondisi Barang')
                ->options([
                    'new' => 'New',
                    'used' => 'Used',
                ])
                ->default('new')
                ->required()
                ->helperText('Kondisi barang sesuai skema (new/used).'),

            TextInput::make('price')
                ->label('Harga')
                ->numeric()
                ->minValue(0)
                ->prefix('Rp')
                ->required()
                ->helperText('Harga jual utama (Rupiah).'),

            TextInput::make('promo_price')
                ->label('Harga Promo (opsional)')
                ->numeric()
                ->prefix('Rp')
                ->rule('lt:price')
                ->helperText('Jika diisi, harus lebih kecil dari harga.'),

            DateTimePicker::make('promo_ends_at')
                ->label('Promo Berakhir (opsional)')
                ->seconds(false)
                ->helperText('Tanggal/waktu berakhirnya promo. Kosongkan jika tidak ada.'),

            TextInput::make('qty_available')
                ->label('Stok')
                ->numeric()
                ->default(0)
                ->required()
                ->helperText('Jumlah stok tersedia.'),

            TextInput::make('min_order_qty')
                ->label('Min. Order')
                ->numeric()
                ->default(1)
                ->required()
                ->helperText('Jumlah minimal pembelian untuk varian ini.'),

            Select::make('status')
                ->label('Status')
                ->options([
                    'active' => 'Active',
                    'inactive' => 'Inactive',
                    'out_of_stock' => 'Out of stock',
                    'banned' => 'Banned',
                ])
                ->default('active')
                ->required()
                ->helperText('Active: tampil & dapat dibeli. Out of stock: tampil tapi stok 0. Banned: disembunyikan.'),
        ]);
    }

    public static function infolist(Schema $schema): Schema
    {
        return $schema->components([
            TextEntry::make('shop.name')->label('Toko'),
            TextEntry::make('productVariant.product.name')->label('Produk'),
            TextEntry::make('productVariant.name')->label('Varian'),
            TextEntry::make('condition')->label('Kondisi')->badge(),
            TextEntry::make('price')->label('Harga')->money('IDR'),
            TextEntry::make('promo_price')->label('Harga Promo')->money('IDR')->placeholder('-'),
            TextEntry::make('promo_ends_at')->label('Promo Berakhir')->dateTime()->placeholder('-'),
            TextEntry::make('qty_available')->label('Stok'),
            TextEntry::make('min_order_qty')->label('Min. Order'),
            TextEntry::make('status')->label('Status')->badge(),
            TextEntry::make('created_at')->label('Dibuat')->dateTime()->placeholder('-'),
            TextEntry::make('updated_at')->label('Diubah')->dateTime()->placeholder('-'),
            TextEntry::make('deleted_at')
                ->label('Dihapus')
                ->dateTime()
                ->visible(fn (VendorListing $record): bool => $record->trashed()),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('shop.name')
                    ->label('Toko')
                    ->sortable()
                    ->searchable(),

                TextColumn::make('productVariant.product.name')
                    ->label('Produk')
                    ->sortable()
                    ->searchable(),

                TextColumn::make('productVariant.name')
                    ->label('Varian')
                    ->sortable()
                    ->searchable(),

                TextColumn::make('condition')
                    ->label('Kondisi')
                    ->badge(),

                TextColumn::make('price')
                    ->label('Harga')
                    ->money('IDR')
                    ->sortable(),

                TextColumn::make('promo_price')
                    ->label('Harga Promo')
                    ->money('IDR')
                    ->sortable(),

                TextColumn::make('promo_ends_at')
                    ->label('Promo Berakhir')
                    ->dateTime()
                    ->sortable(),

                TextColumn::make('qty_available')
                    ->label('Stok')
                    ->sortable(),

                TextColumn::make('min_order_qty')
                    ->label('Min. Order')
                    ->sortable(),

                TextColumn::make('status')
                    ->label('Status')
                    ->badge(),

                TextColumn::make('created_at')
                    ->label('Dibuat')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('updated_at')
                    ->label('Diubah')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('deleted_at')
                    ->label('Dihapus')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                TrashedFilter::make(),

                SelectFilter::make('status')
                    ->label('Status')
                    ->options([
                        'active' => 'Active',
                        'inactive' => 'Inactive',
                        'out_of_stock' => 'Out of stock',
                        'banned' => 'Banned',
                    ]),

                SelectFilter::make('condition')
                    ->label('Kondisi')
                    ->options([
                        'new' => 'New',
                        'used' => 'Used',
                    ]),

                // Filter Shop langsung via relationship
                SelectFilter::make('shop')
                    ->label('Toko')
                    ->relationship('shop', 'name'),

                // Filter Tipe/Brand/Kategori via relasi bertingkat
                SelectFilter::make('product_type')
                    ->label('Tipe Produk')
                    ->options([
                        'device' => 'Device',
                        'liquid' => 'Liquid',
                        'accessory' => 'Accessory',
                    ])
                    ->query(function (Builder $q, array $data) {
                        $value = $data['value'] ?? null;
                        if ($value) {
                            $q->whereHas('productVariant.product', fn (Builder $p) => $p->where('type', $value));
                        }
                    }),

                SelectFilter::make('brand_id')
                    ->label('Brand')
                    ->options(fn () => Brand::query()->orderBy('name')->pluck('name', 'id')->toArray())
                    ->query(function (Builder $q, array $data) {
                        $value = $data['value'] ?? null;
                        if ($value) {
                            $q->whereHas('productVariant.product', fn (Builder $p) => $p->where('brand_id', $value));
                        }
                    }),

                SelectFilter::make('category_id')
                    ->label('Kategori')
                    ->options(fn () => Category::query()->orderBy('name')->pluck('name', 'id')->toArray())
                    ->query(function (Builder $q, array $data) {
                        $value = $data['value'] ?? null;
                        if ($value) {
                            $q->whereHas('productVariant.product', fn (Builder $p) => $p->where('primary_category_id', $value));
                        }
                    }),
            ])
            ->recordActions([
                ViewAction::make(),
                EditAction::make(),

                DeleteAction::make()
                    ->requiresConfirmation()
                    ->modalHeading('Hapus Listing')
                    ->modalDescription('Listing akan dipindahkan ke trash. Anda bisa memulihkannya dari menu Trashed.'),

                ForceDeleteAction::make()
                    ->requiresConfirmation()
                    ->modalHeading('Hapus Permanen')
                    ->modalDescription('Tindakan ini tidak dapat dibatalkan. Hapus permanen listing beserta riwayat terkait.'),

                RestoreAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make()
                        ->requiresConfirmation(),
                    ForceDeleteBulkAction::make()
                        ->requiresConfirmation(),
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

    public static function getRecordRouteBindingEloquentQuery(): Builder
    {
        return parent::getRecordRouteBindingEloquentQuery()
            ->withoutGlobalScopes([
                SoftDeletingScope::class,
            ]);
    }
}
