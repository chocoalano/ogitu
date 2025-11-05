<?php

namespace App\Filament\Store\Resources\WalletAccounts;

use App\Filament\Store\Resources\WalletAccounts\Pages\ManageWalletAccounts;
use App\Models\Shop;
use App\Models\Vendor;
use App\Models\WalletAccount;
use BackedEnum;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Infolists\Components\TextEntry;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class WalletAccountResource extends Resource
{
    protected static ?string $model = WalletAccount::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedBanknotes;

    /**
     * Ambil semua shop_id milik vendor yang sedang login.
     */
    protected static function currentVendorShopIds(): array
    {
        $customerId = auth('customer')->id();
        if (! $customerId) {
            return [-1];
        }

        /** @var Vendor|null $vendor */
        $vendor = Vendor::query()->where('customer_id', $customerId)->first();
        if (! $vendor) {
            return [-1];
        }

        return Shop::query()
            ->where('vendor_id', $vendor->id)
            ->pluck('id')
            ->all() ?: [-1];
    }

    /**
     * Scope data: hanya wallet milik shop vendor (owner_type = shop).
     */
    public static function getEloquentQuery(): Builder
    {
        $shopIds = self::currentVendorShopIds();

        return parent::getEloquentQuery()
            ->where('owner_type', 'shop')
            ->whereIn('owner_id', $shopIds);
    }

    public static function form(Schema $schema): Schema
    {
        $shopIds = self::currentVendorShopIds();
        $shopOptions = Shop::query()
            ->whereIn('id', $shopIds)
            ->pluck('name', 'id')
            ->toArray();

        return $schema->components([
            Select::make('owner_type')
                ->label('Jenis Pemilik')
                ->options(['shop' => 'Shop (Toko)'])
                ->default('shop')
                ->disabled()
                ->helperText('Terkunci ke tipe "shop" karena ini adalah panel toko.'),
            Select::make('owner_id')
                ->label('Toko')
                ->options($shopOptions)
                ->disabled()
                ->helperText('Rekening ini terikat ke toko Anda. Tidak dapat diubah oleh vendor.'),
            TextInput::make('currency')
                ->label('Mata Uang')
                ->default('IDR')
                ->maxLength(3)
                ->disabled()
                ->helperText('Kode mata uang ISO 4217 (3 huruf), default: IDR.'),
            TextInput::make('balance')
                ->label('Saldo (cache)')
                ->numeric()
                ->prefix('Rp')
                ->disabled()
                ->helperText('Saldo ini adalah cache. Sumber kebenaran saldo ada pada Ledger (double-entry).'),
            Select::make('status')
                ->label('Status')
                ->options(['active' => 'Active', 'suspended' => 'Suspended'])
                ->disabled()
                ->helperText('Status operasional rekening. Perubahan hanya oleh admin.'),
        ]);
    }

    public static function infolist(Schema $schema): Schema
    {
        return $schema->components([
            TextEntry::make('owner_type')
                ->label('Jenis Pemilik')
                ->badge(),
            TextEntry::make('owner_id')
                ->label('Toko')
                ->formatStateUsing(fn ($state) => optional(Shop::find($state))->name ?? "Shop #{$state}"),
            TextEntry::make('currency')->label('Mata Uang'),
            TextEntry::make('balance')->label('Saldo')->money('IDR'),
            TextEntry::make('status')->label('Status')->badge(),
            TextEntry::make('created_at')->label('Dibuat')->dateTime()->placeholder('-'),
            TextEntry::make('updated_at')->label('Diubah')->dateTime()->placeholder('-'),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('owner_type')->label('Jenis')->badge(),
                TextColumn::make('owner_id')
                    ->label('Toko')
                    ->formatStateUsing(fn ($state) => optional(Shop::find($state))->name ?? "Shop #{$state}")
                    ->searchable(),
                TextColumn::make('currency')->label('Currency'),
                TextColumn::make('balance')->label('Saldo')->money('IDR')->sortable(),
                TextColumn::make('status')->label('Status')->badge(),
                TextColumn::make('created_at')->label('Dibuat')->dateTime()->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('updated_at')->label('Diubah')->dateTime()->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([])
            ->recordActions([
                ViewAction::make(), // read-only di panel toko
            ])
            ->headerActions([])   // hilangkan Create
            ->bulkActions([]);    // hilangkan bulk delete/edit
    }

    public static function getPages(): array
    {
        return [
            'index' => ManageWalletAccounts::route('/'),
        ];
    }
}
