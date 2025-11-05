<?php

namespace App\Filament\Admin\Resources\Categories;

use App\Filament\Admin\Resources\Categories\Pages\ManageCategories;
use App\Models\Category;
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
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Str;
use UnitEnum;

class CategoryResource extends Resource
{
    protected static string|UnitEnum|null $navigationGroup = 'Master Data';

    protected static ?string $model = Category::class;

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->with(['parent'])->withCount('children');
    }

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Grid::make(12)->schema([
                // Parent category
                Select::make('parent_id')
                    ->label('Parent')
                    ->options(fn (?Category $record) => Category::query()
                        ->when($record?->id, fn ($q) => $q->whereKeyNot($record->id))
                        ->orderBy('name')
                        ->pluck('name', 'id')
                        ->toArray()
                    )
                    ->searchable()
                    ->native(false)
                    ->helperText('Opsional. Pilih induk untuk membuat hirarki kategori.')
                    ->columnSpan(4),

                // Name
                TextInput::make('name')
                    ->label('Nama')
                    ->required()
                    ->maxLength(120)
                    ->reactive()
                    ->afterStateUpdated(fn (Set $set, $state) => $set('slug', Str::slug((string) $state)))
                    ->helperText('Nama kategori seperti "Vape Device", "Liquid Freebase", dll.')
                    ->columnSpan(8),

                // Slug
                TextInput::make('slug')
                    ->label('Slug')
                    ->required()
                    ->maxLength(140)
                    ->reactive()
                    ->unique(ignoreRecord: true)
                    ->afterStateUpdated(function (Set $set, Get $get, $state) {
                        // update path saat slug berubah
                        $parentId = $get('parent_id');
                        $slug = Str::slug((string) $state);
                        $parentPath = '/';
                        if ($parentId) {
                            $p = Category::query()->select(['id', 'path'])->find($parentId);
                            $parentPath = rtrim($p?->path ?? '/', '/').'/';
                        }
                        $set('path', $parentPath.$slug);
                    })
                    ->helperText('Slug URL-friendly. Otomatis dari nama, bisa disunting.')
                    ->columnSpan(6),

                // Path (auto)
                TextInput::make('path')
                    ->label('Path')
                    ->required()
                    ->maxLength(255)
                    ->unique(ignoreRecord: true)
                    ->disabled()           // diset otomatis
                    ->dehydrated(true)     // tetap kirim ke server
                    ->helperText('Otomatis: gabungan parent + slug, contoh: /device/pods')
                    ->columnSpan(6),

                Toggle::make('is_age_restricted')
                    ->label('18+')
                    ->default(false)
                    ->required()
                    ->helperText('Aktifkan jika kategori berisi produk terbatas usia (contoh: liquid nikotin).')
                    ->columnSpan(12),
            ])->columnSpanFull(),
        ]);
    }

    public static function infolist(Schema $schema): Schema
    {
        return $schema->components([
            TextEntry::make('parent.name')->label('Parent')->placeholder('-'),
            TextEntry::make('name')->label('Nama'),
            TextEntry::make('slug')->label('Slug'),
            TextEntry::make('path')->label('Path'),
            IconEntry::make('is_age_restricted')->label('18+')->boolean(),
            TextEntry::make('children_count')->label('Jumlah Subkategori'),
            TextEntry::make('created_at')->label('Dibuat')->dateTime()->placeholder('-'),
            TextEntry::make('updated_at')->label('Diubah')->dateTime()->placeholder('-'),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')->label('Nama')->searchable()->sortable(),
                TextColumn::make('parent.name')->label('Parent')->placeholder('-')->sortable(),
                TextColumn::make('slug')->label('Slug')->searchable(),
                TextColumn::make('path')->label('Path')->searchable()->toggleable(),
                IconColumn::make('is_age_restricted')->label('18+')->boolean(),
                TextColumn::make('children_count')->label('Sub')->sortable()->toggleable(),
                TextColumn::make('created_at')->label('Dibuat')->dateTime()->sortable()->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('updated_at')->label('Diubah')->dateTime()->sortable()->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('parent_id')
                    ->label('Parent')
                    ->options(fn () => Category::orderBy('name')->pluck('name', 'id')->toArray()),

                TernaryFilter::make('age_only')
                    ->label('Hanya 18+?')
                    ->queries(
                        true: fn (Builder $q) => $q->where('is_age_restricted', true),
                        false: fn (Builder $q) => $q->where('is_age_restricted', false),
                        blank: fn (Builder $q) => $q
                    )
                    ->placeholder('Semua'),
            ])
            ->recordActions([
                ViewAction::make(),
                EditAction::make(),
                DeleteAction::make(),
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
            'index' => ManageCategories::route('/'),
        ];
    }
}
