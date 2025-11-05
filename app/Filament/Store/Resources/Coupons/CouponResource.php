<?php

namespace App\Filament\Store\Resources\Coupons;

use App\Filament\Store\Resources\Coupons\Pages\ManageCoupons;
use App\Models\Coupon;
use App\Models\Shop;
use App\Models\Vendor;
use BackedEnum;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Set;
use Filament\Infolists\Components\TextEntry;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;

class CouponResource extends Resource
{
    protected static ?string $model = Coupon::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedTicket;

    /** Ambil semua shop_id milik vendor (berdasarkan customer yang login) */
    protected static function currentVendorShopIds(): array
    {
        $customerId = Auth::id();
        if (! $customerId) {
            return [-1];
        }

        $vendorId = Vendor::query()->where('customer_id', $customerId)->value('id');
        if (! $vendorId) {
            return [-1];
        }

        return Shop::query()->where('vendor_id', $vendorId)->pluck('id')->all() ?: [-1];
    }

    /** Scope data: hanya kupon milik toko vendor & applies_to = shop */
    public static function getEloquentQuery(): Builder
    {
        $shopIds = self::currentVendorShopIds();

        return parent::getEloquentQuery()
            ->where('applies_to', 'shop')
            ->whereIn('shop_id', $shopIds);
    }

    public static function form(Schema $schema): Schema
    {
        $shopIds = self::currentVendorShopIds();
        $shopOptions = Shop::query()->whereIn('id', $shopIds)->pluck('name', 'id')->toArray();

        return $schema->components([
            TextInput::make('code')
                ->label('Kode Kupon')
                ->required()
                ->maxLength(40)
                ->unique(ignoreRecord: true)
                ->reactive()
                ->afterStateUpdated(function (Set $set, $state) {
                    // Uppercase & hilangkan spasi
                    $set('code', Str::upper(preg_replace('/\s+/', '', (string) $state)));
                })
                ->helperText('Kode unik tanpa spasi, huruf besar otomatis. Contoh: SASVAPE10.'),

            Select::make('type')
                ->label('Tipe Diskon')
                ->options(['percent' => 'Percent (%)', 'amount' => 'Amount (IDR)'])
                ->required()
                ->native(false)
                ->helperText('Pilih persentase (%) atau nominal rupiah (IDR).'),

            TextInput::make('value')
                ->label('Nilai Diskon')
                ->numeric()
                ->required()
                ->minValue(1)
                ->rules([
                    // Jika percent, batasi ≤ 95
                    fn (callable $get) => $get('type') === 'percent' ? 'max:95' : null,
                ])
                ->prefix(fn (callable $get) => $get('type') === 'amount' ? 'Rp' : null)
                ->suffix(fn (callable $get) => $get('type') === 'percent' ? '%' : null)
                ->helperText(fn (callable $get) => $get('type') === 'percent'
                        ? 'Persen diskon 1–95%.'
                        : 'Nominal diskon rupiah, misal 20000 untuk Rp20.000.'
                ),

            // Lock applies_to ke shop agar vendor tidak bisa membuat kupon platform
            Select::make('applies_to')
                ->label('Berlaku Untuk')
                ->options(['shop' => 'Shop (Toko)'])
                ->default('shop')
                ->disabled()
                ->helperText('Kupon toko only. Kupon platform hanya dapat dibuat oleh admin.'),

            Select::make('shop_id')
                ->label('Toko')
                ->options($shopOptions)
                ->required()
                ->searchable()
                ->helperText('Pilih toko Anda yang menerima kupon ini.'),

            TextInput::make('min_order')
                ->label('Min. Order (IDR)')
                ->numeric()
                ->minValue(0)
                ->default(null)
                ->prefix('Rp')
                ->helperText('Minimal nilai belanja agar kupon dapat digunakan. Biarkan kosong jika tidak ada batas.'),

            TextInput::make('max_uses')
                ->label('Batas Pemakaian')
                ->numeric()
                ->minValue(1)
                ->default(null)
                ->helperText('Jumlah pemakaian maksimum untuk semua pelanggan. Kosongkan jika unlimited.'),

            TextInput::make('used')
                ->label('Telah Dipakai')
                ->numeric()
                ->default(0)
                ->disabled()
                ->helperText('Counter terpakai. Diupdate otomatis saat checkout.'),

            DateTimePicker::make('starts_at')
                ->label('Mulai')
                ->seconds(false)
                ->helperText('Waktu mulai kupon aktif. Kosongkan untuk aktif segera.'),

            DateTimePicker::make('ends_at')
                ->label('Berakhir')
                ->seconds(false)
                ->rule('after_or_equal:starts_at')
                ->helperText('Batas akhir kupon. Harus ≥ waktu mulai jika diisi.'),

            Select::make('status')
                ->label('Status')
                ->options(['active' => 'Active', 'inactive' => 'Inactive'])
                ->default('active')
                ->required()
                ->native(false)
                ->helperText('Active: kupon dapat dipakai (jika dalam periode). Inactive: kupon nonaktif.'),
        ]);
    }

    public static function infolist(Schema $schema): Schema
    {
        return $schema->components([
            TextEntry::make('code')->label('Kode'),
            TextEntry::make('type')->label('Tipe')->badge(),
            TextEntry::make('value')->label('Nilai')->formatStateUsing(function ($state, $record) {
                return $record->type === 'percent'
                    ? $state.'%'
                    : 'Rp '.number_format((int) $state, 0, ',', '.');
            }),
            TextEntry::make('shop_id')->label('Toko')->formatStateUsing(
                fn ($state) => optional(Shop::find($state))->name ?? "Shop #{$state}"
            ),
            TextEntry::make('min_order')->label('Min. Order')->formatStateUsing(fn ($s) => $s ? 'Rp '.number_format((int) $s, 0, ',', '.') : '-'),
            TextEntry::make('max_uses')->label('Batas Pemakaian')->placeholder('-'),
            TextEntry::make('used')->label('Terpakai'),
            TextEntry::make('starts_at')->label('Mulai')->dateTime()->placeholder('-'),
            TextEntry::make('ends_at')->label('Berakhir')->dateTime()->placeholder('-'),
            TextEntry::make('status')->label('Status')->badge(),
            TextEntry::make('created_at')->label('Dibuat')->dateTime()->placeholder('-'),
            TextEntry::make('updated_at')->label('Diubah')->dateTime()->placeholder('-'),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('code')->label('Kode')->searchable(),
                TextColumn::make('type')->label('Tipe')->badge(),
                TextColumn::make('value')->label('Nilai')->formatStateUsing(function ($state, $record) {
                    return $record->type === 'percent'
                        ? $state.'%'
                        : 'Rp '.number_format((int) $state, 0, ',', '.');
                })->sortable(),
                TextColumn::make('shop_id')
                    ->label('Toko')
                    ->formatStateUsing(fn ($state) => optional(Shop::find($state))->name ?? "Shop #{$state}")
                    ->searchable(),
                TextColumn::make('min_order')->label('Min. Order')->formatStateUsing(fn ($s) => $s ? 'Rp '.number_format((int) $s, 0, ',', '.') : '-')->sortable(),
                TextColumn::make('max_uses')->label('Batas')->sortable(),
                TextColumn::make('used')->label('Terpakai')->sortable(),
                TextColumn::make('starts_at')->label('Mulai')->dateTime()->sortable(),
                TextColumn::make('ends_at')->label('Berakhir')->dateTime()->sortable(),
                TextColumn::make('status')->label('Status')->badge(),
                TextColumn::make('created_at')->label('Dibuat')->dateTime()->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('updated_at')->label('Diubah')->dateTime()->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([])
            ->recordActions([
                ViewAction::make(),
                EditAction::make(),
                DeleteAction::make(),
            ])
            ->headerActions([
                // Vendor BISA membuat kupon toko
                // (action Create ada di ManageCoupons)
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
            'index' => ManageCoupons::route('/'),
        ];
    }
}
