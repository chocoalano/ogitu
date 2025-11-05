<?php

namespace App\Filament\Admin\Resources\Shops;

use App\Filament\Admin\Resources\Shops\Pages\ManageShops;
use App\Models\Address;
use App\Models\Shop;
use App\Models\Vendor;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Infolists\Components\TextEntry;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Str;
use UnitEnum;

class ShopResource extends Resource
{
    protected static ?string $model = Shop::class;

    protected static string|UnitEnum|null $navigationGroup = 'User & Vendor';

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->with(['vendor', 'vendor.customer'])
            ->withCount('vendor_listings'); // jika relasi ada
    }

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            // VENDOR
            Select::make('vendor_id')
                ->label('Vendor')
                ->options(fn () => Vendor::query()
                    ->with('customer')
                    ->orderBy('company_name')
                    ->get()
                    ->mapWithKeys(function (Vendor $v) {
                        $owner = $v->customer?->name ? " • {$v->customer->name}" : '';

                        return [$v->id => "{$v->company_name}{$owner} (ID {$v->id})"];
                    })
                    ->toArray()
                )
                ->searchable()
                ->required()
                ->helperText('Pilih vendor pemilik toko ini.'),

            // NAME & SLUG
            TextInput::make('name')
                ->label('Nama Toko')
                ->required()
                ->maxLength(150)
                ->reactive()
                ->afterStateUpdated(function (Set $set, $state, $old) {
                    $current = $set('slug');
                    if (blank($current) || $current === Str::slug((string) $old)) {
                        $set('slug', Str::slug((string) $state));
                    }
                })
                ->helperText('Nama publik toko.'),

            TextInput::make('slug')
                ->label('Slug')
                ->required()
                ->maxLength(160)
                ->unique(ignoreRecord: true)
                ->helperText('Slug unik (huruf kecil, tanpa spasi). Contoh: "sas-vape-store".'),

            // DESKRIPSI
            Textarea::make('description')
                ->label('Deskripsi')
                ->rows(4)
                ->columnSpanFull()
                ->helperText('Deskripsi toko yang tampil di halaman profil.'),

            // PICKUP ADDRESS — DI FILTER BERDASARKAN CUSTOMER DARI VENDOR
            Select::make('pickup_address_id')
                ->label('Alamat Pickup (opsional)')
                ->options(function (Get $get) {
                    $vendorId = $get('vendor_id');
                    if (! $vendorId) {
                        return [];
                    }
                    $customerId = Vendor::query()->whereKey($vendorId)->value('customer_id');
                    if (! $customerId) {
                        return [];
                    }

                    return Address::query()
                        ->where('customer_id', $customerId)
                        ->orderByDesc('is_default_shipping')
                        ->orderByDesc('is_default_billing')
                        ->get()
                        ->mapWithKeys(function (Address $a) {
                            $label = trim("{$a->recipient_name} • {$a->line1} {$a->district}, {$a->city} {$a->province} {$a->postal_code}");

                            return [$a->id => $label];
                        })
                        ->toArray();
                })
                ->searchable()
                ->helperText('Alamat penjemputan/retur untuk toko. Hanya alamat milik pemilik vendor.'),

            // RATING (READ-ONLY)
            TextInput::make('rating_avg')
                ->label('Rating Rata-rata')
                ->numeric()
                ->default(0.0)
                ->disabled()
                ->prefix('★')
                ->helperText('Dihitung otomatis dari ulasan; tidak dapat diubah manual.'),

            // STATUS
            Select::make('status')
                ->label('Status')
                ->options([
                    'open' => 'Open',
                    'closed' => 'Closed',
                    'suspended' => 'Suspended',
                ])
                ->default('open')
                ->required()
                ->helperText('Open: aktif. Closed: tutup sementara. Suspended: dibekukan oleh admin.'),
        ]);
    }

    public static function infolist(Schema $schema): Schema
    {
        return $schema->components([
            TextEntry::make('vendor.company_name')->label('Vendor'),
            TextEntry::make('vendor.customer.name')->label('Pemilik (Customer)')->placeholder('-'),
            TextEntry::make('name')->label('Nama Toko'),
            TextEntry::make('slug')->label('Slug'),
            TextEntry::make('description')->label('Deskripsi')->placeholder('-')->columnSpanFull(),
            TextEntry::make('pickup_address_id')->label('Alamat Pickup')->formatStateUsing(function ($state) {
                if (! $state) {
                    return '-';
                }
                $a = Address::find($state);

                return $a
                    ? trim("{$a->recipient_name} • {$a->line1} {$a->district}, {$a->city} {$a->province} {$a->postal_code}")
                    : '-';
            }),
            TextEntry::make('rating_avg')->label('Rating')->suffix(' ★'),
            TextEntry::make('status')->label('Status')->badge(),
            TextEntry::make('created_at')->label('Dibuat')->dateTime()->placeholder('-'),
            TextEntry::make('updated_at')->label('Diubah')->dateTime()->placeholder('-'),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('vendor.company_name')->label('Vendor')->searchable(),
                TextColumn::make('name')->label('Nama')->searchable(),
                TextColumn::make('slug')->label('Slug')->searchable(),
                TextColumn::make('pickup_address_id')
                    ->label('Pickup')
                    ->formatStateUsing(function ($state) {
                        if (! $state) {
                            return '-';
                        }
                        $a = Address::find($state);

                        return $a ? "{$a->city}, {$a->province}" : '-';
                    })
                    ->toggleable(),
                TextColumn::make('rating_avg')->label('Rating')->suffix(' ★')->sortable(),
                TextColumn::make('status')->label('Status')->badge(),
                TextColumn::make('created_at')->label('Dibuat')->dateTime()->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('updated_at')->label('Diubah')->dateTime()->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('status')
                    ->label('Status')
                    ->options([
                        'open' => 'Open',
                        'closed' => 'Closed',
                        'suspended' => 'Suspended',
                    ]),
                SelectFilter::make('vendor')
                    ->label('Vendor')
                    ->relationship('vendor', 'company_name'),
                TernaryFilter::make('has_pickup')
                    ->label('Punya Pickup Address?')
                    ->queries(
                        true: fn (Builder $q) => $q->whereNotNull('pickup_address_id'),
                        false: fn (Builder $q) => $q->whereNull('pickup_address_id'),
                        blank: fn (Builder $q) => $q
                    )
                    ->placeholder('Semua'),
            ])
            ->recordActions([
                ViewAction::make(),
                EditAction::make(),
                DeleteAction::make()
                    ->requiresConfirmation()
                    ->modalHeading('Hapus Toko')
                    ->modalDescription('Pastikan tidak ada data kritikal sebelum menghapus toko.'),
            ])
            ->headerActions([
                // create default tersedia dari Manage page
            ])
            ->bulkActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make()->requiresConfirmation(),
                ]),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ManageShops::route('/'),
        ];
    }
}
