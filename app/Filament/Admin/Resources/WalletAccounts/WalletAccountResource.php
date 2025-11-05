<?php

namespace App\Filament\Admin\Resources\WalletAccounts;

use App\Filament\Admin\Resources\WalletAccounts\Pages\ManageWalletAccounts;
use App\Models\Customer;
use App\Models\Escrow;
use App\Models\Shop;
use App\Models\WalletAccount;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Infolists\Components\TextEntry;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Support\Str;
use UnitEnum;

class WalletAccountResource extends Resource
{
    protected static ?string $model = WalletAccount::class;

    protected static string|UnitEnum|null $navigationGroup = 'Keuangan (Ledger/Wallet)';

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Select::make('owner_type')
                ->label('Jenis Pemilik')
                ->options([
                    'customer' => 'Customer',
                    'shop' => 'Shop',
                    'platform' => 'Platform',
                    'escrow' => 'Escrow',
                ])
                ->required()
                ->reactive()
                ->helperText('Tentukan pemilik rekening dompet. Platform untuk akun milik sistem/platform.'),

            // Owner ID akan menampilkan opsi sesuai owner_type
            Select::make('owner_id')
                ->label('Pemilik')
                ->options(function (Get $get) {
                    return match ($get('owner_type')) {
                        'customer' => Customer::query()
                            ->orderBy('name')
                            ->limit(500)
                            ->get()
                            ->mapWithKeys(fn ($c) => [$c->id => "{$c->name} (#{$c->id})"])
                            ->toArray(),
                        'shop' => Shop::query()
                            ->orderBy('name')
                            ->limit(500)
                            ->get()
                            ->mapWithKeys(fn ($s) => [$s->id => "{$s->name} (#{$s->id})"])
                            ->toArray(),
                        'escrow' => Escrow::query()
                            ->latest('id')
                            ->limit(500)
                            ->get()
                            ->mapWithKeys(fn ($e) => [$e->id => "Escrow #{$e->id}"])
                            ->toArray(),
                        'platform' => [0 => 'Platform Root (#0)'],
                        default => [],
                    };
                })
                ->searchable()
                ->required()
                ->default(fn (Get $get) => $get('owner_type') === 'platform' ? 0 : null)
                ->disabled(fn (Get $get) => $get('owner_type') === 'platform')
                ->helperText('Pilih entitas pemilik sesuai jenis. Untuk platform, owner_id terkunci ke 0.'),

            TextInput::make('currency')
                ->label('Mata Uang')
                ->default('IDR')
                ->maxLength(3)
                ->reactive()
                ->afterStateUpdated(fn (Set $set, $state) => $set('currency', Str::upper(substr((string) $state, 0, 3))))
                ->helperText('Kode mata uang ISO 4217 (3 huruf). Contoh: IDR, USD, SGD.'),

            TextInput::make('balance')
                ->label('Saldo (cache)')
                ->numeric()
                ->default(0.00)
                ->disabled()
                ->helperText('Saldo ini adalah cache. Sumber kebenaran saldo ada pada Ledger (double-entry). Jangan ubah manual.'),

            Select::make('status')
                ->label('Status')
                ->options(['active' => 'Active', 'suspended' => 'Suspended'])
                ->default('active')
                ->required()
                ->helperText('Active: dapat dipakai. Suspended: dibekukan (transaksi ditolak).'),
        ]);
    }

    public static function infolist(Schema $schema): Schema
    {
        return $schema->components([
            TextEntry::make('owner_type')
                ->label('Jenis Pemilik')
                ->badge(),

            TextEntry::make('owner_id')
                ->label('Pemilik')
                ->formatStateUsing(function ($state, WalletAccount $record) {
                    return match ($record->owner_type) {
                        'customer' => optional(Customer::find($state))->name ?:
                            "Customer #{$state}",
                        'shop' => optional(Shop::find($state))->name ?:
                            "Shop #{$state}",
                        'escrow' => "Escrow #{$state}",
                        'platform' => 'Platform Root (#0)',
                        default => "#{$state}",
                    };
                }),

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
                    ->label('Pemilik')
                    ->formatStateUsing(function ($state, WalletAccount $record) {
                        return match ($record->owner_type) {
                            'customer' => optional(Customer::find($state))->name ?:
                                "Customer #{$state}",
                            'shop' => optional(Shop::find($state))->name ?:
                                "Shop #{$state}",
                            'escrow' => "Escrow #{$state}",
                            'platform' => 'Platform Root (#0)',
                            default => "#{$state}",
                        };
                    })
                    ->searchable(),
                TextColumn::make('currency')->label('Currency')->searchable(),
                TextColumn::make('balance')->label('Saldo')->money('IDR')->sortable(),
                TextColumn::make('status')->label('Status')->badge(),
                TextColumn::make('created_at')->label('Dibuat')->dateTime()->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('updated_at')->label('Diubah')->dateTime()->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('owner_type')
                    ->label('Jenis')
                    ->options([
                        'customer' => 'Customer',
                        'shop' => 'Shop',
                        'platform' => 'Platform',
                        'escrow' => 'Escrow',
                    ]),
                SelectFilter::make('status')
                    ->label('Status')
                    ->options(['active' => 'Active', 'suspended' => 'Suspended']),
                SelectFilter::make('currency')
                    ->label('Currency')
                    ->options([
                        'IDR' => 'IDR',
                        'USD' => 'USD',
                        'SGD' => 'SGD',
                    ]),
            ])
            ->recordActions([
                ViewAction::make(),
                EditAction::make(),
                // Hati-hati menghapus akun wallet. Biasanya tidak disarankan di produksi.
                DeleteAction::make()
                    ->requiresConfirmation()
                    ->modalHeading('Hapus Wallet Account')
                    ->modalDescription('Menghapus wallet dapat menyebabkan ketidaksesuaian historis bila tidak diarsip. Pastikan Anda paham risikonya.'),
            ])
            ->headerActions([
                // Create via Manage page (default Filament behavior)
            ])
            ->bulkActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make()
                        ->requiresConfirmation()
                        ->modalHeading('Hapus Massal Wallet Account')
                        ->modalDescription('Tindakan ini berisiko. Pertimbangkan menandai suspended daripada menghapus.'),
                ]),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ManageWalletAccounts::route('/'),
        ];
    }
}
