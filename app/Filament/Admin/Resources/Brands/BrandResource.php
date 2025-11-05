<?php

namespace App\Filament\Admin\Resources\Brands;

use App\Filament\Admin\Resources\Brands\Pages\ManageBrands;
use App\Models\Brand;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\TextInput;
use Filament\Infolists\Components\TextEntry;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\Layout\Grid;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Str;
use UnitEnum;

class BrandResource extends Resource
{
    protected static string|UnitEnum|null $navigationGroup = 'Master Data';

    protected static ?string $model = Brand::class;

    public static function getEloquentQuery(): Builder
    {
        // Tampilkan jumlah produk per brand (untuk kolom & filter)
        return parent::getEloquentQuery()->withCount('products');
    }

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Grid::make(12)->schema([
                TextInput::make('name')
                    ->label('Nama Brand')
                    ->required()
                    ->maxLength(120)
                    ->reactive()
                    ->afterStateUpdated(fn (Set $set, $state) => $set('slug', Str::slug((string) $state)))
                    ->helperText('Contoh: Uwell, Caliburn, VUSE, VEEV, Foom, dsb.')
                    ->columnSpan(6),

                TextInput::make('slug')
                    ->label('Slug')
                    ->required()
                    ->maxLength(140)
                    ->unique(ignoreRecord: true)
                    ->reactive()
                    ->afterStateUpdated(fn (Set $set, $state) => $set('slug', Str::slug((string) $state)))
                    ->helperText('Slug URL-friendly untuk brand. Otomatis dari nama, tapi bisa disunting.')
                    ->columnSpan(6),
            ])->columnSpanFull(),
        ]);
    }

    public static function infolist(Schema $schema): Schema
    {
        return $schema->components([
            TextEntry::make('name')->label('Nama'),
            TextEntry::make('slug')->label('Slug'),
            TextEntry::make('products_count')->label('Jumlah Produk'),
            TextEntry::make('created_at')->label('Dibuat')->dateTime()->placeholder('-'),
            TextEntry::make('updated_at')->label('Diubah')->dateTime()->placeholder('-'),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')
                    ->label('Nama')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('slug')
                    ->label('Slug')
                    ->searchable(),

                TextColumn::make('products_count')
                    ->label('Produk')
                    ->sortable()
                    ->tooltip('Jumlah produk yang terhubung dengan brand ini'),

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
                TernaryFilter::make('has_products')
                    ->label('Memiliki Produk?')
                    ->placeholder('Semua')
                    ->queries(
                        true: fn (Builder $q) => $q->has('products'),
                        false: fn (Builder $q) => $q->doesntHave('products'),
                        blank: fn (Builder $q) => $q
                    ),
            ])
            ->recordActions([
                ViewAction::make(),
                EditAction::make(),
                DeleteAction::make()
                    ->requiresConfirmation()
                    ->modalHeading('Hapus Brand')
                    ->modalDescription('Menghapus brand dapat mempengaruhi referensi pada produk. Pastikan tidak ada produk aktif yang terkait.'),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make()->requiresConfirmation(),
                ]),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ManageBrands::route('/'),
        ];
    }
}
