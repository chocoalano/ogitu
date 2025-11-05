<?php

namespace App\Filament\Admin\Resources\Products;

use App\Filament\Admin\Resources\Products\Pages\ManageProducts;
use App\Models\Brand;
use App\Models\Category;
use App\Models\Product;
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
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Filters\TrashedFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Illuminate\Support\Str;
use UnitEnum;

class ProductResource extends Resource
{
    protected static ?string $model = Product::class;

    protected static string|UnitEnum|null $navigationGroup = 'Master Data';

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->with(['brand', 'category'])
            ->withCount('product_variants');
    }

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Select::make('brand_id')
                ->label('Brand (opsional)')
                ->options(fn () => Brand::orderBy('name')->pluck('name', 'id')->toArray())
                ->searchable()
                ->helperText('Pilih brand produk. Boleh dikosongkan untuk house brand / generic.'),

            Select::make('primary_category_id')
                ->label('Kategori Utama')
                ->options(fn () => Category::orderBy('name')->pluck('name', 'id')->toArray())
                ->searchable()
                ->required()
                ->helperText('Kategori utama produk untuk navigasi & filter katalog.'),

            Select::make('type')
                ->label('Tipe Produk')
                ->options([
                    'device' => 'Device',
                    'liquid' => 'Liquid',
                    'accessory' => 'Accessory',
                ])
                ->required()
                ->native(false)
                ->helperText('Device: mod/pod/disposable. Liquid: e-liquid. Accessory: coil, cotton, charger, tools.'),

            TextInput::make('name')
                ->label('Nama Produk')
                ->required()
                ->maxLength(150)
                ->reactive()
                ->afterStateUpdated(function (Set $set, $state, $old) {
                    $current = $set('slug');
                    if (blank($current) || $current === Str::slug((string) $old)) {
                        $set('slug', Str::slug((string) $state));
                    }
                })
                ->helperText('Nama produk di katalog. Contoh: "Uwell Caliburn G4", "VUSE GO 1000".'),

            TextInput::make('slug')
                ->label('Slug')
                ->required()
                ->maxLength(160)
                ->unique(ignoreRecord: true)
                ->helperText('Slug unik (huruf kecil, tanpa spasi). Contoh: "uwell-caliburn-g4".'),

            Textarea::make('description')
                ->label('Deskripsi (opsional)')
                ->rows(4)
                ->columnSpanFull()
                ->helperText('Deskripsi pemasaran / spesifikasi singkat.'),

            Toggle::make('is_active')
                ->label('Aktif')
                ->default(true)
                ->helperText('Nonaktifkan untuk menyembunyikan produk dari katalog.'),

            Toggle::make('is_age_restricted')
                ->label('Batasi Usia (18+)')
                ->default(true)
                ->helperText('Aktifkan untuk produk yang memerlukan verifikasi umur.'),

            Textarea::make('specs')
                ->label('Specs (opsional)')
                ->rows(3)
                ->helperText('Spesifikasi tambahan (teks bebas / JSON ringkas).'),
        ]);
    }

    public static function infolist(Schema $schema): Schema
    {
        return $schema->components([
            TextEntry::make('brand.name')->label('Brand')->placeholder('-'),
            TextEntry::make('category.name')->label('Kategori'),
            TextEntry::make('type')->label('Tipe')->badge(),
            TextEntry::make('name')->label('Nama'),
            TextEntry::make('slug')->label('Slug'),
            TextEntry::make('description')->label('Deskripsi')->placeholder('-')->columnSpanFull(),
            IconEntry::make('is_active')->label('Aktif')->boolean(),
            IconEntry::make('is_age_restricted')->label('18+')->boolean(),
            TextEntry::make('variants_count')->label('Jumlah Varian'),
            TextEntry::make('created_at')->label('Dibuat')->dateTime()->placeholder('-'),
            TextEntry::make('updated_at')->label('Diubah')->dateTime()->placeholder('-'),
            TextEntry::make('deleted_at')
                ->label('Dihapus')
                ->dateTime()
                ->visible(fn (Product $record): bool => $record->trashed()),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('brand.name')->label('Brand')->badge()->sortable()->toggleable(),
                TextColumn::make('category.name')->label('Kategori')->badge()->sortable()->toggleable(),

                TextColumn::make('type')->label('Tipe')->badge()->sortable(),

                TextColumn::make('name')->label('Nama')->searchable(),
                TextColumn::make('slug')->label('Slug')->searchable()->toggleable(),

                TextColumn::make('variants_count')
                    ->label('Varian')
                    ->counts('product_variants')
                    ->sortable(),

                IconColumn::make('is_active')->label('Aktif')->boolean(),
                IconColumn::make('is_age_restricted')->label('18+')->boolean(),

                TextColumn::make('created_at')->label('Dibuat')->dateTime()->sortable()->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('updated_at')->label('Diubah')->dateTime()->sortable()->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('deleted_at')->label('Dihapus')->dateTime()->sortable()->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                TrashedFilter::make(),

                SelectFilter::make('type')
                    ->label('Tipe')
                    ->options([
                        'device' => 'Device',
                        'liquid' => 'Liquid',
                        'accessory' => 'Accessory',
                    ]),

                SelectFilter::make('brand')
                    ->label('Brand')
                    ->relationship('brand', 'name'),

                SelectFilter::make('category')
                    ->label('Kategori')
                    ->relationship('category', 'name'),

                TernaryFilter::make('is_active')
                    ->label('Aktif?')
                    ->placeholder('Semua'),

                TernaryFilter::make('is_age_restricted')
                    ->label('Butuh 18+?')
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
            'index' => ManageProducts::route('/'),
        ];
    }

    public static function getRecordRouteBindingEloquentQuery(): Builder
    {
        return parent::getRecordRouteBindingEloquentQuery()
            ->withoutGlobalScopes([SoftDeletingScope::class]);
    }
}
