<?php

namespace App\Filament\Admin\Resources\ProductVariants;

use App\Filament\Admin\Resources\ProductVariants\Pages\ManageProductVariants;
use App\Models\Brand;
use App\Models\Category;
use App\Models\Product;
use App\Models\ProductVariant;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ForceDeleteAction;
use Filament\Actions\ForceDeleteBulkAction;
use Filament\Actions\RestoreAction;
use Filament\Actions\RestoreBulkAction;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Infolists\Components\IconEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Filters\TrashedFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use UnitEnum;

class ProductVariantResource extends Resource
{
    protected static ?string $model = ProductVariant::class;

    protected static string|UnitEnum|null $navigationGroup = 'Master Data';

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->with(['product.brand', 'product.category']);
    }

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            // PRODUCT
            Select::make('product_id')
                ->label('Produk')
                ->options(fn () => Product::query()
                    ->orderBy('name')
                    ->pluck('name', 'id')
                    ->toArray()
                )
                ->searchable()
                ->required()
                ->reactive()
                ->helperText('Varian selalu terkait ke satu produk di katalog pusat.'),

            // SKU & BARCODE
            TextInput::make('sku')
                ->label('SKU')
                ->required()
                ->maxLength(100)
                ->unique(ignoreRecord: true)
                ->helperText('Kode unik internal. Contoh: CAL-G4-BLK-30ML-S25.'),
            TextInput::make('barcode')
                ->label('Barcode (opsional)')
                ->maxLength(100)
                ->helperText('EAN/UPC/QR code jika ada.'),

            // NAMA VARIAN
            TextInput::make('name')
                ->label('Nama Varian')
                ->required()
                ->maxLength(150)
                ->helperText('Contoh: Black 30ml 25mg / 0.8Ω / Red Apple.'),

            // GENERIK / DEVICE / LIQUID / ACCESSORY
            TextInput::make('color')
                ->label('Warna (opsional)')
                ->maxLength(60)
                ->helperText('Contoh: Black, Silver, Transparent.'),

            TextInput::make('capacity_ml')
                ->label('Isi (ml, untuk liquid/disposable)')
                ->numeric()
                ->minValue(0)
                ->helperText('Kosongkan jika tidak relevan (mis. coil/aksesoris).'),

            Select::make('nicotine_type')
                ->label('Nicotine Type (liquid)')
                ->options([
                    'FREEBASE' => 'Freebase',
                    'SALT' => 'Salt',
                ])
                ->helperText('Pilih jika varian adalah liquid; biarkan kosong jika bukan liquid.'),

            TextInput::make('nicotine_mg')
                ->label('Nicotine (mg)')
                ->numeric()
                ->minValue(0)
                ->helperText('Contoh: 3 / 6 / 25 / 30. Hanya untuk liquid.'),

            TextInput::make('vg_ratio')
                ->label('VG (%)')
                ->numeric()
                ->minValue(0)->maxValue(100)
                ->helperText('Rasio VG. Untuk liquid, umumnya 50–80.'),

            TextInput::make('pg_ratio')
                ->label('PG (%)')
                ->numeric()
                ->minValue(0)->maxValue(100)
                ->helperText('Rasio PG. Untuk liquid, total VG+PG sebaiknya mendekati 100.'),

            TextInput::make('coil_resistance_ohm')
                ->label('Resistansi Coil (Ω)')
                ->numeric()
                ->minValue(0)
                ->helperText('Isi untuk varian coil/atomizer/pod; contoh: 0.8.'),

            TextInput::make('puff_count')
                ->label('Puff Count (disposable)')
                ->numeric()
                ->minValue(0)
                ->helperText('Estimasi jumlah hisapan untuk device disposable.'),

            Textarea::make('specs')
                ->label('Spesifikasi (opsional)')
                ->rows(3)
                ->helperText('Catatan spesifikasi tambahan (teks/JSON ringkas).'),

            Toggle::make('is_active')
                ->label('Aktif')
                ->default(true)
                ->helperText('Nonaktifkan untuk menyembunyikan varian dari katalog.'),
        ]);
    }

    public static function infolist(Schema $schema): Schema
    {
        return $schema->components([
            TextEntry::make('product.name')->label('Produk'),
            TextEntry::make('sku')->label('SKU'),
            TextEntry::make('barcode')->label('Barcode')->placeholder('-'),
            TextEntry::make('name')->label('Nama Varian'),

            TextEntry::make('color')->label('Warna')->placeholder('-'),
            TextEntry::make('capacity_ml')->label('Isi (ml)')->placeholder('-'),
            TextEntry::make('nicotine_type')->label('Nicotine')->badge()->placeholder('-'),
            TextEntry::make('nicotine_mg')->label('Nicotine (mg)')->placeholder('-'),
            TextEntry::make('vg_ratio')->label('VG (%)')->placeholder('-'),
            TextEntry::make('pg_ratio')->label('PG (%)')->placeholder('-'),
            TextEntry::make('coil_resistance_ohm')->label('Ω')->placeholder('-'),
            TextEntry::make('puff_count')->label('Puff')->placeholder('-'),

            IconEntry::make('is_active')->label('Aktif')->boolean(),

            TextEntry::make('created_at')->label('Dibuat')->dateTime()->placeholder('-'),
            TextEntry::make('updated_at')->label('Diubah')->dateTime()->placeholder('-'),
            TextEntry::make('deleted_at')
                ->label('Dihapus')
                ->dateTime()
                ->visible(fn (ProductVariant $record): bool => $record->trashed()),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('product.name')
                    ->label('Produk')
                    ->sortable()
                    ->searchable(),

                TextColumn::make('sku')
                    ->label('SKU')
                    ->searchable(),

                TextColumn::make('name')
                    ->label('Varian')
                    ->searchable(),

                TextColumn::make('product.brand.name')
                    ->label('Brand')
                    ->badge()
                    ->toggleable()
                    ->sortable(),

                TextColumn::make('product.category.name')
                    ->label('Kategori')
                    ->badge()
                    ->toggleable()
                    ->sortable(),

                TextColumn::make('color')->label('Warna')->toggleable(),

                TextColumn::make('capacity_ml')->label('ml')->sortable()->toggleable(),
                TextColumn::make('nicotine_type')->label('Nic')->badge()->toggleable(),
                TextColumn::make('nicotine_mg')->label('mg')->sortable()->toggleable(),
                TextColumn::make('vg_ratio')->label('VG%')->sortable()->toggleable(),
                TextColumn::make('pg_ratio')->label('PG%')->sortable()->toggleable(),
                TextColumn::make('coil_resistance_ohm')->label('Ω')->sortable()->toggleable(),
                TextColumn::make('puff_count')->label('Puff')->sortable()->toggleable(),

                IconColumn::make('is_active')->label('Aktif')->boolean(),

                TextColumn::make('created_at')->label('Dibuat')->dateTime()->sortable()->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('updated_at')->label('Diubah')->dateTime()->sortable()->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('deleted_at')->label('Dihapus')->dateTime()->sortable()->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                TrashedFilter::make(),

                // Tipe Produk via relasi product.type
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
                            $q->whereHas('product', fn (Builder $p) => $p->where('type', $value));
                        }
                    }),

                // Brand & Kategori via relasi bertingkat
                SelectFilter::make('brand_id')
                    ->label('Brand')
                    ->options(fn () => Brand::orderBy('name')->pluck('name', 'id')->toArray())
                    ->query(function (Builder $q, array $data) {
                        $value = $data['value'] ?? null;
                        if ($value) {
                            $q->whereHas('product', fn (Builder $p) => $p->where('brand_id', $value));
                        }
                    }),

                SelectFilter::make('category_id')
                    ->label('Kategori')
                    ->options(fn () => Category::orderBy('name')->pluck('name', 'id')->toArray())
                    ->query(function (Builder $q, array $data) {
                        $value = $data['value'] ?? null;
                        if ($value) {
                            $q->whereHas('product', fn (Builder $p) => $p->where('primary_category_id', $value));
                        }
                    }),

                // Nicotine type & Active toggle
                SelectFilter::make('nicotine_type')
                    ->label('Nicotine Type')
                    ->options([
                        'FREEBASE' => 'Freebase',
                        'SALT' => 'Salt',
                    ]),
                TernaryFilter::make('is_active')
                    ->label('Aktif?')
                    ->placeholder('Semua'),
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
            'index' => ManageProductVariants::route('/'),
        ];
    }

    public static function getRecordRouteBindingEloquentQuery(): Builder
    {
        return parent::getRecordRouteBindingEloquentQuery()
            ->withoutGlobalScopes([SoftDeletingScope::class]);
    }
}
