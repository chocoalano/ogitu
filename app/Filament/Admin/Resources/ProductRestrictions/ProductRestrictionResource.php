<?php

namespace App\Filament\Admin\Resources\ProductRestrictions;

use App\Filament\Admin\Resources\ProductRestrictions\Pages\ManageProductRestrictions;
use App\Models\ProductRestriction;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Infolists\Components\IconEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;
use Filament\Tables;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use UnitEnum;

class ProductRestrictionResource extends Resource
{
    protected static ?string $model = ProductRestriction::class;

    // Ganti bila ingin ikon lain
    protected static string|UnitEnum|null $navigationGroup = 'Master Data';

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->with('product');
    }

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            // Produk (searchable)
            Select::make('product_id')
                ->label('Produk')
                ->relationship('product', 'name')
                ->searchable()
                ->preload()
                ->required()
                ->helperText('Pilih produk yang memiliki batasan/regulasi khusus.'),

            // Country code (ISO 3166-1 alpha-2)
            TextInput::make('country_code')
                ->label('Kode Negara (ISO-2)')
                ->default('ID')
                ->maxLength(2)
                ->required()
                ->reactive()
                ->afterStateUpdated(function (Set $set, $state) {
                    $set('country_code', Str::upper(substr((string) $state, 0, 2)));
                })
                ->helperText('Gunakan kode 2 huruf, mis. ID, US, SG.'),

            TextInput::make('state')
                ->label('Provinsi/State (opsional)')
                ->maxLength(100)
                ->helperText('Isi untuk pembatasan tingkat provinsi. Biarkan kosong jika berlaku nasional.'),

            TextInput::make('city')
                ->label('Kota (opsional)')
                ->maxLength(100)
                ->helperText('Isi untuk pembatasan tingkat kota. Dapat dikosongkan.'),

            TextInput::make('min_age')
                ->label('Minimal Usia')
                ->numeric()
                ->minValue(0)
                ->default(18)
                ->required()
                ->helperText('Usia minimum untuk pembelian di wilayah ini. Default 18.'),

            Toggle::make('is_banned')
                ->label('Dilarang Total?')
                ->default(false)
                ->helperText('Aktifkan jika produk benar-benar dilarang di wilayah ini.'),

            // Validasi unik komposit: (product_id, country_code, state, city)
            TextInput::make('composite_unique_guard')
                ->dehydrated(false)
                ->hidden()
                ->rule(function (Get $get) {
                    return Rule::unique('product_restrictions')
                        ->where(fn ($q) => $q
                            ->where('product_id', $get('product_id'))
                            ->where('country_code', $get('country_code'))
                            ->where('state', $get('state'))
                            ->where('city', $get('city'))
                        )
                        ->ignore(request()->route('record')); // abaikan saat edit
                }),
        ]);
    }

    public static function infolist(Schema $schema): Schema
    {
        return $schema->components([
            TextEntry::make('product.name')->label('Produk'),
            TextEntry::make('country_code')->label('Negara'),
            TextEntry::make('state')->label('Provinsi/State')->placeholder('-'),
            TextEntry::make('city')->label('Kota')->placeholder('-'),
            TextEntry::make('min_age')->label('Min. Usia'),
            IconEntry::make('is_banned')->label('Dilarang?')->boolean(),
            TextEntry::make('created_at')->label('Dibuat')->dateTime()->placeholder('-'),
            TextEntry::make('updated_at')->label('Diubah')->dateTime()->placeholder('-'),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('product.name')
                    ->label('Produk')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('country_code')
                    ->label('Negara')
                    ->formatStateUsing(fn ($s) => Str::upper((string) $s))
                    ->searchable(),

                TextColumn::make('state')->label('Provinsi/State')->searchable()->toggleable(),
                TextColumn::make('city')->label('Kota')->searchable()->toggleable(),

                TextColumn::make('min_age')->label('Min. Usia')->sortable(),
                IconColumn::make('is_banned')->label('Dilarang?')->boolean(),

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
            ])
            ->filters([
                // Filter by produk
                SelectFilter::make('product')
                    ->label('Produk')
                    ->relationship('product', 'name'),

                // Filter country
                SelectFilter::make('country_code')
                    ->label('Negara')
                    ->options(fn () => ProductRestriction::query()
                        ->select('country_code')
                        ->distinct()
                        ->orderBy('country_code')
                        ->pluck('country_code', 'country_code')
                        ->mapWithKeys(fn ($c) => [$c => Str::upper($c)])
                        ->toArray()
                    ),

                // Filter banned?
                Tables\Filters\TernaryFilter::make('is_banned')
                    ->label('Dilarang?')
                    ->placeholder('Semua'),

                // Filter rentang usia
                Filter::make('age_range')
                    ->label('Rentang Usia')
                    ->form([
                        TextInput::make('min')->numeric()->label('≥'),
                        TextInput::make('max')->numeric()->label('≤'),
                    ])
                    ->query(function (Builder $q, array $data) {
                        if (! empty($data['min'])) {
                            $q->where('min_age', '>=', (int) $data['min']);
                        }
                        if (! empty($data['max'])) {
                            $q->where('min_age', '<=', (int) $data['max']);
                        }
                    }),
            ])
            ->recordActions([
                ViewAction::make(),
                EditAction::make(),
                DeleteAction::make(),
            ])
            ->headerActions([
                // Create default tersedia dari Manage page
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ManageProductRestrictions::route('/'),
        ];
    }
}
