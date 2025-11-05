<?php

namespace App\Filament\Store\Resources\Shops;

use App\Filament\Store\Resources\Shops\Pages\ManageShops;
use App\Models\Address;
use App\Models\Shop;
use App\Models\Vendor;
use BackedEnum;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Set;
use Filament\Infolists\Components\TextEntry;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;

class ShopResource extends Resource
{
    protected static ?string $model = Shop::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedBuildingStorefront;

    /** Ambil vendor_id milik user (customer) yang login */
    protected static function currentVendorId(): ?int
    {
        $customerId = Auth::id();
        if (! $customerId) {
            return null;
        }

        return Vendor::query()
            ->where('customer_id', $customerId)
            ->value('id');
    }

    /** Ambil opsi alamat milik customer pemilik vendor (untuk pickup_address) */
    protected static function currentCustomerAddressOptions(): array
    {
        $customerId = Auth::id();
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
    }

    /** Scope semua query hanya ke shop milik vendor yang login */
    public static function getEloquentQuery(): Builder
    {
        $vendorId = self::currentVendorId() ?? -1;

        return parent::getEloquentQuery()
            ->withoutGlobalScopes([SoftDeletingScope::class])
            ->where('vendor_id', $vendorId);
    }

    public static function form(Schema $schema): Schema
    {
        $vendorId = self::currentVendorId();
        $addressOptions = self::currentCustomerAddressOptions();

        return $schema->components([
            // Dikunci ke vendor pemilik
            TextInput::make('vendor_id')
                ->label('Vendor ID')
                ->default($vendorId)
                ->disabled()
                ->helperText('Toko ini terikat ke vendor Anda dan tidak dapat diubah.'),

            TextInput::make('name')
                ->label('Nama Toko')
                ->required()
                ->maxLength(150)
                ->reactive()
                ->afterStateUpdated(function (Set $set, $state, $old) {
                    // Auto slug hanya saat slug masih kosong atau masih mengikuti slug lama
                    $current = $set('slug'); // baca nilai sekarang
                    if (blank($current) || $current === Str::slug((string) $old)) {
                        $set('slug', Str::slug((string) $state));
                    }
                })
                ->helperText('Nama publik toko Anda.'),

            TextInput::make('slug')
                ->label('Slug')
                ->required()
                ->maxLength(160)
                ->unique(ignoreRecord: true)
                ->helperText('Alamat unik toko (huruf kecil, tanpa spasi). Contoh: "sas-vape-store".'),

            Textarea::make('description')
                ->label('Deskripsi')
                ->rows(4)
                ->columnSpanFull()
                ->helperText('Deskripsi singkat toko, muncul di halaman profil toko.'),

            Select::make('pickup_address_id')
                ->label('Alamat Pickup')
                ->options($addressOptions)
                ->searchable()
                ->helperText('Alamat penjemputan/retur. Hanya alamat milik akun Anda yang bisa dipilih. Biarkan kosong jika belum ada.'),

            TextInput::make('rating_avg')
                ->label('Rating Rata-rata')
                ->numeric()
                ->prefix('★')
                ->disabled()
                ->default(0.0)
                ->helperText('Nilai ini dihitung otomatis dari ulasan; tidak bisa diubah manual.'),

            Select::make('status')
                ->label('Status')
                ->options([
                    'open' => 'Open',
                    'closed' => 'Closed',
                    'suspended' => 'Suspended',
                ])
                ->default('open')
                ->required()
                ->helperText('Open: toko aktif. Closed: tutup sementara. Suspended: dibekukan oleh admin.'),
        ]);
    }

    public static function infolist(Schema $schema): Schema
    {
        return $schema->components([
            TextEntry::make('name')->label('Nama Toko'),
            TextEntry::make('slug')->label('Slug'),
            TextEntry::make('description')->label('Deskripsi')->placeholder('-')->columnSpanFull(),
            TextEntry::make('pickup_address_id')
                ->label('Alamat Pickup')
                ->formatStateUsing(function ($state) {
                    $a = $state ? Address::find($state) : null;

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
                TextColumn::make('name')->label('Nama')->searchable(),
                TextColumn::make('slug')->label('Slug')->searchable(),
                TextColumn::make('pickup_address_id')
                    ->label('Alamat Pickup')
                    ->formatStateUsing(function ($state) {
                        $a = $state ? Address::find($state) : null;

                        return $a ? "{$a->city}, {$a->province}" : '-';
                    })
                    ->toggleable(),
                TextColumn::make('rating_avg')->label('Rating')->suffix(' ★')->sortable(),
                TextColumn::make('status')->label('Status')->badge(),
                TextColumn::make('created_at')->label('Dibuat')->dateTime()->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('updated_at')->label('Diubah')->dateTime()->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([])
            ->recordActions([
                ViewAction::make(),
                EditAction::make(),
                // Hapus Delete agar vendor tidak menghapus toko tanpa persetujuan admin:
                // DeleteAction::make(),
            ])
            ->headerActions([
                // Biasanya vendor tidak membuat toko baru tanpa approval admin.
                // Jika ingin izinkan 1 vendor > 1 shop, aktifkan action Create di sini.
            ])
            ->bulkActions([
                BulkActionGroup::make([
                    // DeleteBulkAction::make(),
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
