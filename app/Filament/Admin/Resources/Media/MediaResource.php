<?php

namespace App\Filament\Admin\Resources\Media;

use App\Filament\Admin\Resources\Media\Pages\ManageMedia;
use App\Models\Brand;
use App\Models\Category;
use App\Models\Medium;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\Shop;
use App\Models\Vendor;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ForceDeleteAction;
use Filament\Actions\ForceDeleteBulkAction;
use Filament\Actions\RestoreAction;
use Filament\Actions\RestoreBulkAction;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Infolists\Components\IconEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use UnitEnum;

class MediaResource extends Resource
{
    /** Jika pakai Medium model, ganti ke Medium::class */
    protected static ?string $model = Medium::class;

    protected static string|UnitEnum|null $navigationGroup = 'Konten & Review';

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery();
    }

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Grid::make(12)->schema([
                // ===================== Owner ======================
                Select::make('owner_type')
                    ->label('Tipe Pemilik')
                    ->options([
                        'product' => 'Product',
                        'product_variant' => 'Product Variant',
                        'brand' => 'Brand',
                        'category' => 'Category',
                        'shop' => 'Shop',
                        'vendor' => 'Vendor',
                    ])
                    ->required()
                    ->reactive()
                    ->columnSpan(4)
                    ->helperText('Media ini terhubung ke entitas apa. Pilih sesuai kebutuhan.'),

                Select::make('owner_id')
                    ->label('Pemilik')
                    ->options(function (Get $get) {
                        return match ($get('owner_type')) {
                            'product' => Product::query()
                                ->orderBy('name')->limit(500)
                                ->pluck('name', 'id')->toArray(),
                            'product_variant' => ProductVariant::query()
                                ->with('product')
                                ->limit(500)->get()
                                ->mapWithKeys(fn ($v) => [$v->id => ($v->product?->name ? "{$v->product->name} — {$v->name}" : $v->name)])
                                ->toArray(),
                            'brand' => Brand::query()
                                ->orderBy('name')->limit(500)
                                ->pluck('name', 'id')->toArray(),
                            'category' => Category::query()
                                ->orderBy('name')->limit(500)
                                ->pluck('name', 'id')->toArray(),
                            'shop' => Shop::query()
                                ->orderBy('name')->limit(500)
                                ->pluck('name', 'id')->toArray(),
                            'vendor' => Vendor::query()
                                ->orderBy('company_name')->limit(500)
                                ->pluck('company_name', 'id')->toArray(),
                            default => [],
                        };
                    })
                    ->searchable()
                    ->required()
                    ->columnSpan(8)
                    ->helperText('Pilih entitas pemilik sesuai tipe di sebelah kiri.'),

                // ===================== Klasifikasi ======================
                Select::make('collection')
                    ->label('Koleksi')
                    ->options([
                        'thumbnail' => 'Thumbnail',
                        'gallery' => 'Gallery',
                        'detail' => 'Detail',
                        'banner' => 'Banner',
                        'manual' => 'Manual / PDF',
                        'spec' => 'Spec Sheet',
                        'video' => 'Video',
                    ])
                    ->required()
                    ->columnSpan(4)
                    ->helperText('Klasifikasi media untuk penempatan & urutan tampilan.'),

                Select::make('status')
                    ->label('Status')
                    ->options([
                        'active' => 'Active',
                        'inactive' => 'Inactive',
                    ])
                    ->default('active')
                    ->required()
                    ->columnSpan(4)
                    ->helperText('Inactive untuk menyembunyikan media tanpa menghapus.'),

                TextInput::make('sort_order')
                    ->label('Urutan')
                    ->numeric()
                    ->default(0)
                    ->columnSpan(4)
                    ->helperText('Angka prioritas. Lebih kecil = tampil lebih dulu di koleksi yang sama.'),

                // ===================== Upload & Metadata ======================
                FileUpload::make('path')
                    ->label('Berkas')
                    ->disk('public')          // sesuaikan disk storage
                    ->directory('media')      // folder penyimpanan
                    ->visibility('public')
                    ->imageEditor()
                    ->preserveFilenames()     // simpan nama asli
                    ->openable()
                    ->downloadable()
                    ->required()
                    ->columnSpan(6)
                    ->helperText('Unggah gambar/video/dokumen. Path file akan tersimpan di kolom "path".'),

                TextInput::make('file_name')
                    ->label('Nama Berkas')
                    ->maxLength(255)
                    ->columnSpan(6)
                    ->helperText('Opsional. Jika kosong, gunakan nama file dari upload.'),

                TextInput::make('mime_type')
                    ->label('MIME Type')
                    ->maxLength(100)
                    ->columnSpan(4)
                    ->helperText('Contoh: image/jpeg, image/png, video/mp4, application/pdf.'),

                TextInput::make('size_bytes')
                    ->label('Ukuran (bytes)')
                    ->numeric()
                    ->columnSpan(4)
                    ->helperText('Ukuran file dalam bytes. Opsional.'),

                Grid::make(12)->schema([
                    TextInput::make('width')
                        ->label('Lebar (px)')
                        ->numeric()
                        ->columnSpan(6),
                    TextInput::make('height')
                        ->label('Tinggi (px)')
                        ->numeric()
                        ->columnSpan(6),
                ])->columnSpan(12),

                TextInput::make('alt_text')
                    ->label('Alt Text')
                    ->maxLength(255)
                    ->columnSpan(6)
                    ->helperText('Deskripsi singkat untuk aksesibilitas & SEO (wajib untuk gambar produk utama).'),

                TextInput::make('title')
                    ->label('Judul')
                    ->maxLength(255)
                    ->columnSpan(6)
                    ->helperText('Judul media (opsional).'),

                Textarea::make('caption')
                    ->label('Caption/Keterangan')
                    ->rows(3)
                    ->columnSpanFull()
                    ->helperText('Keterangan tambahan yang dapat ditampilkan di halaman produk.'),
            ])->columnSpanFull(),
        ]);
    }

    public static function infolist(Schema $schema): Schema
    {
        return $schema->components([
            TextEntry::make('owner_type')->label('Tipe Pemilik')->badge(),
            TextEntry::make('owner_id')->label('Pemilik (ID)'),
            TextEntry::make('collection')->label('Koleksi')->badge(),
            TextEntry::make('path')->label('Path'),
            TextEntry::make('file_name')->label('Nama Berkas')->placeholder('-'),
            TextEntry::make('mime_type')->label('MIME')->placeholder('-'),
            TextEntry::make('size_bytes')->label('Ukuran (bytes)')->placeholder('-'),
            TextEntry::make('width')->label('Lebar')->placeholder('-'),
            TextEntry::make('height')->label('Tinggi')->placeholder('-'),
            TextEntry::make('alt_text')->label('Alt')->placeholder('-'),
            TextEntry::make('title')->label('Judul')->placeholder('-'),
            TextEntry::make('caption')->label('Caption')->placeholder('-')->columnSpanFull(),
            TextEntry::make('status')->label('Status')->badge(),
            TextEntry::make('created_at')->label('Dibuat')->dateTime()->placeholder('-'),
            TextEntry::make('updated_at')->label('Diubah')->dateTime()->placeholder('-'),
            IconEntry::make('deleted_at')->label('Terhapus')->boolean()
                ->visible(fn ($record) => method_exists($record, 'trashed') ? $record->trashed() : false),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                ImageColumn::make('path')
                    ->label('Preview')
                    ->disk('public')
                    ->height('56')
                    ->circular(),

                TextColumn::make('collection')->label('Koleksi')->badge()->sortable(),
                TextColumn::make('owner_type')->label('Owner')->badge()->sortable(),
                TextColumn::make('owner_id')->label('Owner ID')->sortable()->toggleable(),
                TextColumn::make('file_name')->label('Nama')->searchable()->toggleable(),
                TextColumn::make('mime_type')->label('MIME')->toggleable(),
                TextColumn::make('size_bytes')->label('Bytes')->sortable()->toggleable(),
                TextColumn::make('status')->label('Status')->badge()->sortable(),
                TextColumn::make('created_at')->label('Dibuat')->dateTime()->sortable()->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('updated_at')->label('Diubah')->dateTime()->sortable()->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('deleted_at')->label('Dihapus')->dateTime()->sortable()->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('owner_type')
                    ->label('Owner')
                    ->options([
                        'product' => 'Product',
                        'product_variant' => 'Product Variant',
                        'brand' => 'Brand',
                        'category' => 'Category',
                        'shop' => 'Shop',
                        'vendor' => 'Vendor',
                    ]),

                SelectFilter::make('collection')
                    ->label('Koleksi')
                    ->options([
                        'thumbnail' => 'Thumbnail',
                        'gallery' => 'Gallery',
                        'detail' => 'Detail',
                        'banner' => 'Banner',
                        'manual' => 'Manual / PDF',
                        'spec' => 'Spec Sheet',
                        'video' => 'Video',
                    ]),

                TernaryFilter::make('is_image_only')
                    ->label('Hanya Gambar?')
                    ->queries(
                        true: fn (Builder $q) => $q->where('mime_type', 'like', 'image/%'),
                        false: fn (Builder $q) => $q->where('mime_type', 'not like', 'image/%'),
                        blank: fn (Builder $q) => $q
                    )
                    ->placeholder('Semua'),

                Filter::make('created_range')
                    ->label('Tanggal Dibuat')
                    ->form([
                        \Filament\Forms\Components\DatePicker::make('from')->label('Dari'),
                        \Filament\Forms\Components\DatePicker::make('to')->label('Sampai'),
                    ])
                    ->query(function (Builder $q, array $data) {
                        if (! empty($data['from'])) {
                            $q->whereDate('created_at', '>=', $data['from']);
                        }
                        if (! empty($data['to'])) {
                            $q->whereDate('created_at', '<=', $data['to']);
                        }
                    }),
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
            'index' => ManageMedia::route('/'),
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
