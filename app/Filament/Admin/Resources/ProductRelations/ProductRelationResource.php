<?php

namespace App\Filament\Admin\Resources\ProductRelations;

use App\Filament\Admin\Resources\ProductRelations\Pages\ManageProductRelations;
use App\Models\Brand;
use App\Models\Category;
use App\Models\Product;
use App\Models\ProductRelation;
use Filament\Actions\Action;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Select;
use Filament\Infolists\Components\TextEntry;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Validation\Rule;
use UnitEnum;

class ProductRelationResource extends Resource
{
    protected static ?string $model = ProductRelation::class;

    protected static string|UnitEnum|null $navigationGroup = 'Master Data';

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->with(['product.brand', 'product.category', 'relatedProduct.brand', 'relatedProduct.category']);
    }

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            // Produk A
            Select::make('product_id')
                ->label('Produk')
                ->relationship(name: 'product', titleAttribute: 'name')
                ->searchable()
                ->preload()
                ->required()
                ->helperText('Produk sumber (A).'),

            // Produk B (opsi disaring ≠ product_id)
            Select::make('relatedProduct_id')
                ->label('Produk Terkait')
                ->searchable()
                ->preload()
                ->options(function (Get $get) {
                    $exclude = (int) ($get('product_id') ?? 0);

                    return Product::query()
                        ->when($exclude, fn ($q) => $q->whereKeyNot($exclude))
                        ->orderBy('name')
                        ->pluck('name', 'id')
                        ->toArray();
                })
                ->required()
                ->rules([
                    // Cegah self relation
                    fn (Get $get) => Rule::notIn([(int) ($get('product_id') ?? 0)]),
                ])
                ->helperText('Produk target (B). Tidak boleh sama dengan Produk.'),

            // Jenis relasi (enum konsisten skema)
            Select::make('relation_type')
                ->label('Tipe Relasi')
                ->options([
                    'compatible_with' => 'Compatible with (A kompatibel dengan B)',
                    'recommended_with' => 'Recommended with (disarankan bersama B)',
                    'uses' => 'Uses (A menggunakan B)',
                    'replacement_for' => 'Replacement for (A pengganti B)',
                ])
                ->required()
                ->helperText('Pilih tipe relasi sesuai konteks. "Compatible" & "Recommended" bersifat simetris.'),

            // Validasi unik komposit: (product_id, relatedProduct_id, relation_type)
            Hidden::make('composite_guard')->dehydrated(false)->rule(function (Get $get) {
                return Rule::unique('product_relations')
                    ->where(fn ($q) => $q
                        ->where('product_id', (int) $get('product_id'))
                        ->where('relatedProduct_id', (int) $get('relatedProduct_id'))
                        ->where('relation_type', (string) $get('relation_type'))
                    )
                    ->ignore(request()->route('record')); // abaikan saat edit
            }),
        ]);
    }

    public static function infolist(Schema $schema): Schema
    {
        return $schema->components([
            TextEntry::make('product.name')->label('Produk'),
            TextEntry::make('relatedProduct.name')->label('Produk Terkait'),
            TextEntry::make('relation_type')->label('Tipe')->badge(),
            TextEntry::make('created_at')->label('Dibuat')->dateTime()->placeholder('-'),
            TextEntry::make('updated_at')->label('Diubah')->dateTime()->placeholder('-'),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('product.name')->label('Produk')->searchable()->sortable(),
                TextColumn::make('relatedProduct.name')->label('Produk Terkait')->searchable()->sortable(),
                TextColumn::make('relation_type')->label('Relasi')->badge()->sortable(),

                TextColumn::make('product.brand.name')->label('Brand A')->badge()->toggleable(),
                TextColumn::make('relatedProduct.brand.name')->label('Brand B')->badge()->toggleable(),

                TextColumn::make('product.category.name')->label('Kategori A')->badge()->toggleable(),
                TextColumn::make('relatedProduct.category.name')->label('Kategori B')->badge()->toggleable(),

                TextColumn::make('created_at')->label('Dibuat')->dateTime()->toggleable(isToggledHiddenByDefault: true)->sortable(),
                TextColumn::make('updated_at')->label('Diubah')->dateTime()->toggleable(isToggledHiddenByDefault: true)->sortable(),
            ])
            ->filters([
                SelectFilter::make('relation_type')
                    ->label('Tipe Relasi')
                    ->options([
                        'compatible_with' => 'Compatible with',
                        'recommended_with' => 'Recommended with',
                        'uses' => 'Uses',
                        'replacement_for' => 'Replacement for',
                    ]),

                // Filter Brand & Category lewat relasi bertingkat
                SelectFilter::make('brand_a')
                    ->label('Brand A')
                    ->options(fn () => Brand::orderBy('name')->pluck('name', 'id')->toArray())
                    ->query(function (Builder $q, array $data) {
                        $value = $data['value'] ?? null;
                        if ($value) {
                            $q->whereHas('product', fn (Builder $p) => $p->where('brand_id', $value));
                        }
                    }),

                SelectFilter::make('brand_b')
                    ->label('Brand B')
                    ->options(fn () => Brand::orderBy('name')->pluck('name', 'id')->toArray())
                    ->query(function (Builder $q, array $data) {
                        $value = $data['value'] ?? null;
                        if ($value) {
                            $q->whereHas('relatedProduct', fn (Builder $p) => $p->where('brand_id', $value));
                        }
                    }),

                SelectFilter::make('category_a')
                    ->label('Kategori A')
                    ->options(fn () => Category::orderBy('name')->pluck('name', 'id')->toArray())
                    ->query(function (Builder $q, array $data) {
                        $value = $data['value'] ?? null;
                        if ($value) {
                            $q->whereHas('product', fn (Builder $p) => $p->where('primary_category_id', $value));
                        }
                    }),

                SelectFilter::make('category_b')
                    ->label('Kategori B')
                    ->options(fn () => Category::orderBy('name')->pluck('name', 'id')->toArray())
                    ->query(function (Builder $q, array $data) {
                        $value = $data['value'] ?? null;
                        if ($value) {
                            $q->whereHas('relatedProduct', fn (Builder $p) => $p->where('primary_category_id', $value));
                        }
                    }),
            ])
            ->recordActions([
                ViewAction::make(),
                EditAction::make(),

                // Aksi cepat: buat relasi kebalikan untuk tipe simetris
                Action::make('makeReverse')
                    ->label('Buat Relasi Kebalikan')
                    ->icon(Heroicon::ArrowDownOnSquare)
                    ->visible(function (ProductRelation $record) {
                        return in_array($record->relation_type, ['compatible_with', 'recommended_with'], true);
                    })
                    ->requiresConfirmation()
                    ->action(function (ProductRelation $record) {
                        $exists = ProductRelation::query()
                            ->where('product_id', $record->relatedProduct_id)
                            ->where('relatedProduct_id', $record->product_id)
                            ->where('relation_type', $record->relation_type)
                            ->exists();

                        if (! $exists) {
                            ProductRelation::create([
                                'product_id' => $record->relatedProduct_id,
                                'relatedProduct_id' => $record->product_id,
                                'relation_type' => $record->relation_type,
                            ]);
                        }
                    }),

                DeleteAction::make(),
            ])
            ->headerActions([
                // Create default ada di Manage page
            ])
            ->bulkActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ManageProductRelations::route('/'),
        ];
    }
}
