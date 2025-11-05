<?php

namespace App\Filament\Admin\Resources\Escrows;

use App\Filament\Admin\Resources\Escrows\Pages\ManageEscrows;
use App\Models\Escrow;
use App\Models\WalletAccount;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use UnitEnum;

class EscrowResource extends Resource
{
    protected static ?string $model = Escrow::class;

    protected static string|UnitEnum|null $navigationGroup = 'Keuangan (Ledger/Wallet)';

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->with(['order_shop', 'wallet_account']);
    }

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            // ORDER SHOP (sub-order per toko)
            Select::make('order_shop_id')
                ->label('Order (Per Toko)')
                ->relationship('order_shop', 'id')
                ->searchable()
                ->preload()
                ->required()
                ->helperText('Sub-order milik toko yang dananya ditahan. Biasanya 1 pesanan = beberapa order_shop.'),

            // WALLET ACCOUNT TUJUAN (vendor/shop)
            Select::make('wallet_account_id')
                ->label('Wallet Tujuan')
                ->relationship('wallet_account', 'id')
                ->searchable()
                ->getOptionLabelUsing(function ($value) {
                    if (! $value) {
                        return '';
                    }
                    $w = WalletAccount::query()->find($value);

                    return $w ? "WA#{$w->id} • {$w->owner_type} #{$w->owner_id} • {$w->currency}" : '';
                })
                ->required()
                ->helperText('Rekening e-wallet penerima rilis dana (umumnya milik Shop/Vendor).'),

            // JUMLAH DITAHAN
            TextInput::make('amount_held')
                ->label('Jumlah Ditahan')
                ->numeric()
                ->minValue(0.01)
                ->step(0.01)
                ->required()
                ->helperText('Nominal dana yang ditahan untuk order_shop ini.'),

            // STATUS ESCROW
            Select::make('status')
                ->label('Status')
                ->options([
                    'held' => 'Held (Ditahan)',
                    'released' => 'Released (Dirilis penuh)',
                    'partial_released' => 'Partial Released (Sebagian)',
                    'refunded' => 'Refunded (Dikembalikan ke pembeli)',
                ])
                ->default('held')
                ->required()
                ->reactive()
                ->helperText('Ubah ke Released/Partial/Refunded sesuai hasil pemenuhan & klaim.'),

            // JUMLAH DIRILIS (opsional; validasi ≤ amount_held)
            TextInput::make('released_amount')
                ->label('Jumlah Dirilis')
                ->numeric()
                ->step(0.01)
                ->visible(fn (Get $get) => in_array($get('status'), ['released', 'partial_released'], true))
                ->helperText('Isi saat pelepasan dana (penuh/sebagian). Harus ≤ jumlah ditahan.')
                ->rule(function (Get $get) {
                    $held = (float) ($get('amount_held') ?? 0);

                    return ['nullable', 'numeric', 'min:0', 'max:'.$held];
                }),

            // WAKTU RILIS (opsional)
            DateTimePicker::make('released_at')
                ->label('Waktu Rilis/Refund')
                ->seconds(false)
                ->visible(fn (Get $get) => in_array($get('status'), ['released', 'partial_released', 'refunded'], true))
                ->helperText('Isi waktu eksekusi rilis/refund jika sudah terjadi.'),

            // CATATAN INTERNAL
            Textarea::make('notes')
                ->label('Catatan (opsional)')
                ->rows(3)
                ->columnSpanFull()
                ->helperText('Alasan rilis/refund atau referensi tiket CS untuk audit.'),
        ]);
    }

    public static function infolist(Schema $schema): Schema
    {
        return $schema->components([
            \Filament\Infolists\Components\TextEntry::make('order_shop_id')->label('OrderShop ID'),
            \Filament\Infolists\Components\TextEntry::make('wallet_account.id')->label('WA#'),
            \Filament\Infolists\Components\TextEntry::make('wallet_account.owner_type')->label('Owner Type')->badge(),
            \Filament\Infolists\Components\TextEntry::make('wallet_account.owner_id')->label('Owner ID'),
            \Filament\Infolists\Components\TextEntry::make('amount_held')->label('Ditahan')
                ->formatStateUsing(fn ($s, Escrow $r) => number_format((float) $r->amount_held, 2)),
            \Filament\Infolists\Components\TextEntry::make('released_amount')->label('Dirilis')
                ->placeholder('-')
                ->formatStateUsing(fn ($s, Escrow $r) => is_null($r->released_amount) ? '-' : number_format((float) $r->released_amount, 2)),
            \Filament\Infolists\Components\TextEntry::make('status')->label('Status')->badge(),
            \Filament\Infolists\Components\TextEntry::make('released_at')->label('Waktu Rilis/Refund')->dateTime()->placeholder('-'),
            \Filament\Infolists\Components\TextEntry::make('notes')->label('Catatan')->placeholder('-')->columnSpanFull(),
            \Filament\Infolists\Components\TextEntry::make('created_at')->label('Dibuat')->dateTime()->placeholder('-'),
            \Filament\Infolists\Components\TextEntry::make('updated_at')->label('Diubah')->dateTime()->placeholder('-'),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('id')->label('ESC#')->sortable(),

                TextColumn::make('order_shop_id')
                    ->label('OrderShop')
                    ->sortable(),

                TextColumn::make('wallet_account.id')
                    ->label('WA#')
                    ->sortable(),

                TextColumn::make('wallet_account.owner_type')
                    ->label('Owner')
                    ->badge()
                    ->sortable(),

                TextColumn::make('wallet_account.owner_id')
                    ->label('Owner ID')
                    ->sortable(),

                TextColumn::make('amount_held')
                    ->label('Ditahan')
                    ->money('IDR', divideBy: false)
                    ->sortable(),

                TextColumn::make('released_amount')
                    ->label('Dirilis')
                    ->money('IDR', divideBy: false)
                    ->sortable()
                    ->toggleable(),

                TextColumn::make('status')
                    ->label('Status')
                    ->badge()
                    ->sortable(),

                TextColumn::make('released_at')
                    ->label('Waktu Rilis/Refund')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(),

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
                SelectFilter::make('status')
                    ->label('Status')
                    ->options([
                        'held' => 'Held',
                        'released' => 'Released',
                        'partial_released' => 'Partial Released',
                        'refunded' => 'Refunded',
                    ]),

                // Filter berdasarkan Wallet Account
                SelectFilter::make('wallet_account_id')
                    ->label('Wallet Account')
                    ->options(fn () => WalletAccount::query()
                        ->latest('id')->limit(1000)->get()
                        ->mapWithKeys(fn ($w) => [$w->id => "WA#{$w->id} • {$w->owner_type} #{$w->owner_id}"])
                        ->toArray()
                    ),

                // Filter rentang tanggal rilis
                Filter::make('released_range')
                    ->label('Rentang Rilis/Refund')
                    ->form([
                        \Filament\Forms\Components\DatePicker::make('from')->label('Dari'),
                        \Filament\Forms\Components\DatePicker::make('to')->label('Sampai'),
                    ])
                    ->query(function (Builder $q, array $data) {
                        if (! empty($data['from'])) {
                            $q->whereDate('released_at', '>=', $data['from']);
                        }
                        if (! empty($data['to'])) {
                            $q->whereDate('released_at', '<=', $data['to']);
                        }
                    }),
            ])
            ->recordActions([
                ViewAction::make(),
                EditAction::make()
                    ->tooltip('Perbarui status, nilai rilis, atau catatan.'),
                DeleteAction::make()
                    ->requiresConfirmation()
                    ->modalHeading('Hapus Escrow')
                    ->modalDescription('Menghapus escrow akan menghilangkan catatan penahanan dana. Pastikan tindakan ini sesuai kebijakan audit.'),
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
            'index' => ManageEscrows::route('/'),
        ];
    }
}
