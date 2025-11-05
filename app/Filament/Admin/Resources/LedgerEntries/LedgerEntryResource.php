<?php

namespace App\Filament\Admin\Resources\LedgerEntries;

use App\Filament\Admin\Resources\LedgerEntries\Pages\ManageLedgerEntries;
use App\Models\LedgerEntry;
use App\Models\LedgerTransaction;
use App\Models\WalletAccount;
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
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use UnitEnum;

class LedgerEntryResource extends Resource
{
    protected static ?string $model = LedgerEntry::class;

    protected static string|UnitEnum|null $navigationGroup = 'Keuangan (Ledger/Wallet)';

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->with(['ledger_transaction', 'wallet_account']);
    }

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            // Ledger Transaction
            Select::make('ledger_transaction_id')
                ->label('Ledger Transaction')
                ->relationship('ledger_transaction', 'id')
                ->searchable()
                ->getOptionLabelUsing(function ($value) {
                    if (! $value) {
                        return '';
                    }
                    $tx = LedgerTransaction::query()->select(['id', 'type', 'status', 'occurred_at'])->find($value);

                    return $tx ? sprintf('TX#%d • %s • %s • %s',
                        $tx->id,
                        strtoupper(str_replace('_', ' ', $tx->type)),
                        ucfirst($tx->status),
                        optional($tx->occurred_at)->format('Y-m-d H:i')
                    ) : '';
                })
                ->required()
                ->helperText('Transaksi buku besar induk untuk entri ini.'),

            // Wallet Account
            Select::make('wallet_account_id')
                ->label('Wallet Account')
                ->relationship('wallet_account', 'id')
                ->searchable()
                ->getOptionLabelUsing(function ($value) {
                    if (! $value) {
                        return '';
                    }
                    $wa = WalletAccount::query()->find($value);

                    return $wa ? "WA#{$wa->id} • {$wa->owner_type} #{$wa->owner_id} • {$wa->currency}" : '';
                })
                ->required()
                ->helperText('Rekening e-wallet yang terpengaruh oleh entri ini.'),

            // Direction
            Select::make('direction')
                ->label('Arah')
                ->options(['debit' => 'Debit', 'credit' => 'Credit'])
                ->required()
                ->helperText('Debit menambah sisi kiri akun; Credit sisi kanan. Sistem kamu akan menentukan dampak saldo sesuai jenis akun.'),

            // Amount
            TextInput::make('amount')
                ->label('Jumlah')
                ->numeric()
                ->minValue(0.01)
                ->step(0.01)
                ->required()
                ->helperText('Nominal transaksi dalam mata uang akun (mis. IDR).'),

            // Balance after (opsional, biasanya diisi otomatis oleh service)
            TextInput::make('balance_after')
                ->label('Saldo Setelah')
                ->numeric()
                ->step(0.01)
                ->helperText('Opsional. Umumnya diisi otomatis saat posting transaksi.'),

            Textarea::make('memo')
                ->label('Memo (opsional)')
                ->rows(2)
                ->helperText('Catatan audit singkat untuk entri ini.'),
        ]);
    }

    public static function infolist(Schema $schema): Schema
    {
        return $schema->components([
            TextEntry::make('ledger_transaction.id')->label('TX#'),
            TextEntry::make('ledger_transaction.type')->label('Tipe TX')->badge(),
            TextEntry::make('ledger_transaction.status')->label('Status TX')->badge(),
            TextEntry::make('ledger_transaction.occurred_at')->label('Waktu TX')->dateTime(),

            TextEntry::make('wallet_account.id')->label('WA#'),
            TextEntry::make('wallet_account.owner_type')->label('Owner Type')->badge(),
            TextEntry::make('wallet_account.owner_id')->label('Owner ID'),
            TextEntry::make('wallet_account.currency')->label('Mata Uang'),

            TextEntry::make('direction')->label('Arah')->badge(),
            TextEntry::make('amount')->label('Jumlah')
                ->formatStateUsing(fn ($s, LedgerEntry $r) => number_format((float) $r->amount, 2)),
            TextEntry::make('balance_after')->label('Saldo Setelah')
                ->placeholder('-')
                ->formatStateUsing(fn ($s, LedgerEntry $r) => is_null($r->balance_after) ? '-' : number_format((float) $r->balance_after, 2)),
            TextEntry::make('memo')->label('Memo')->placeholder('-'),

            TextEntry::make('created_at')->label('Dibuat')->dateTime()->placeholder('-'),
            TextEntry::make('updated_at')->label('Diubah')->dateTime()->placeholder('-'),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('ledger_transaction.occurred_at')
                    ->label('Waktu TX')->dateTime()->sortable(),

                TextColumn::make('ledger_transaction.type')
                    ->label('Tipe TX')->badge()->sortable(),

                TextColumn::make('ledger_transaction.status')
                    ->label('Status TX')->badge()->sortable(),

                TextColumn::make('wallet_account.id')
                    ->label('WA#')->sortable(),

                TextColumn::make('wallet_account.owner_type')
                    ->label('Owner')->badge()->sortable(),

                TextColumn::make('wallet_account.owner_id')
                    ->label('Owner ID')->sortable(),

                TextColumn::make('direction')
                    ->label('Arah')->badge()->sortable(),

                TextColumn::make('amount')
                    ->label('Jumlah')
                    ->money('IDR', divideBy: false) // tampilkan sebagai IDR; sesuaikan jika multi-currency
                    ->sortable(),

                TextColumn::make('balance_after')
                    ->label('Saldo Setelah')
                    ->money('IDR', divideBy: false)
                    ->sortable()
                    ->toggleable(),

                TextColumn::make('memo')
                    ->label('Memo')
                    ->wrap()
                    ->toggleable(),

                TextColumn::make('created_at')
                    ->label('Dibuat')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('direction')
                    ->label('Arah')
                    ->options(['debit' => 'Debit', 'credit' => 'Credit']),

                SelectFilter::make('tx_type')
                    ->label('Tipe TX')
                    ->options([
                        'topup' => 'Topup',
                        'purchase_hold' => 'Purchase Hold',
                        'purchase_capture' => 'Purchase Capture',
                        'refund' => 'Refund',
                        'payout' => 'Payout',
                        'withdrawal' => 'Withdrawal',
                        'reversal' => 'Reversal',
                        'fee_capture' => 'Fee Capture',
                    ])
                    ->query(function (Builder $q, array $data) {
                        $val = $data['value'] ?? null;
                        if ($val) {
                            $q->whereHas('ledger_transaction', fn (Builder $t) => $t->where('type', $val));
                        }
                    }),

                SelectFilter::make('tx_status')
                    ->label('Status TX')
                    ->options(['pending' => 'Pending', 'posted' => 'Posted', 'void' => 'Void'])
                    ->query(function (Builder $q, array $data) {
                        $val = $data['value'] ?? null;
                        if ($val) {
                            $q->whereHas('ledger_transaction', fn (Builder $t) => $t->where('status', $val));
                        }
                    }),

                SelectFilter::make('wallet_account_id')
                    ->label('Wallet Account')
                    ->options(fn () => WalletAccount::query()
                        ->latest('id')->limit(1000)->get()
                        ->mapWithKeys(fn ($w) => [$w->id => "WA#{$w->id} • {$w->owner_type} #{$w->owner_id}"])
                        ->toArray()
                    ),

                Filter::make('occurred_range')
                    ->label('Rentang Waktu TX')
                    ->form([
                        \Filament\Forms\Components\DatePicker::make('from')->label('Dari'),
                        \Filament\Forms\Components\DatePicker::make('to')->label('Sampai'),
                    ])
                    ->query(function (Builder $q, array $data) {
                        if (! empty($data['from'])) {
                            $q->whereHas('ledger_transaction', fn (Builder $t) => $t->whereDate('occurred_at', '>=', $data['from'])
                            );
                        }
                        if (! empty($data['to'])) {
                            $q->whereHas('ledger_transaction', fn (Builder $t) => $t->whereDate('occurred_at', '<=', $data['to'])
                            );
                        }
                    }),

                Filter::make('amount_range')
                    ->label('Rentang Nominal')
                    ->form([
                        TextInput::make('min')->numeric()->label('≥'),
                        TextInput::make('max')->numeric()->label('≤'),
                    ])
                    ->query(function (Builder $q, array $data) {
                        if (! empty($data['min'])) {
                            $q->where('amount', '>=', (float) $data['min']);
                        }
                        if (! empty($data['max'])) {
                            $q->where('amount', '<=', (float) $data['max']);
                        }
                    }),
            ])
            ->recordActions([
                ViewAction::make(),
                EditAction::make()
                    ->visible(fn (LedgerEntry $record) => $record->ledger_transaction?->status !== 'posted')
                    ->tooltip('Entri dari transaksi Posted sebaiknya tidak diedit.'),
                DeleteAction::make()
                    ->visible(fn (LedgerEntry $record) => $record->ledger_transaction?->status !== 'posted')
                    ->requiresConfirmation()
                    ->modalHeading('Hapus Ledger Entry')
                    ->modalDescription('Menghapus entri memengaruhi audit trail dan saldo. Pastikan Anda memahami konsekuensinya.'),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make()
                        ->visible(fn () => false), // aman: hindari hapus massal entri ledger
                ]),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ManageLedgerEntries::route('/'),
        ];
    }
}
