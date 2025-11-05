<?php

namespace App\Filament\Admin\Resources\LedgerTransactions;

use App\Filament\Admin\Resources\LedgerTransactions\Pages\ManageLedgerTransactions;
use App\Models\LedgerEntry;
use App\Models\LedgerTransaction;
use App\Models\WalletAccount;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Select;
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

class LedgerTransactionResource extends Resource
{
    protected static ?string $model = LedgerTransaction::class;

    protected static string|UnitEnum|null $navigationGroup = 'Keuangan (Ledger/Wallet)';

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->withCount('ledger_entries')
            ->with(['ledger_entries.wallet_account']);
    }

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Select::make('type')
                ->label('Tipe Transaksi')
                ->options([
                    'topup' => 'Topup',
                    'purchase_hold' => 'Purchase Hold (Auth)',
                    'purchase_capture' => 'Purchase Capture (Settle)',
                    'refund' => 'Refund',
                    'payout' => 'Payout (ke vendor)',
                    'withdrawal' => 'Withdrawal',
                    'reversal' => 'Reversal',
                    'fee_capture' => 'Fee Capture',
                ])
                ->required()
                ->helperText('Kategori transaksi pada buku besar. Menentukan pola entri debit/kredit yang terbentuk.'),

            Select::make('status')
                ->label('Status')
                ->options([
                    'pending' => 'Pending',
                    'posted' => 'Posted',
                    'void' => 'Void',
                ])
                ->default('pending')
                ->required()
                ->helperText('Posted = sudah masuk saldo final. Pending = menunggu proses. Void = dibatalkan.'),

            TextInput::make('ref_type')
                ->label('Referensi Tipe (opsional)')
                ->maxLength(100)
                ->placeholder('order / refund / payout / manual_adjustment')
                ->helperText('Tipe entitas sumber transaksi ini (mis. order, refund). Opsional untuk audit.'),

            TextInput::make('ref_id')
                ->label('Referensi ID (opsional)')
                ->numeric()
                ->helperText('ID entitas sumber (mis. order_id). Opsional.'),

            DateTimePicker::make('occurred_at')
                ->label('Terjadi Pada')
                ->seconds(false)
                ->required()
                ->helperText('Waktu kejadian transaksi (bukan created_at). Dipakai untuk laporan periode.'),
        ]);
    }

    public static function infolist(Schema $schema): Schema
    {
        return $schema->components([
            TextEntry::make('type')->label('Tipe')->badge(),
            TextEntry::make('status')->label('Status')->badge(),
            TextEntry::make('ref_type')->label('Ref. Tipe')->placeholder('-'),
            TextEntry::make('ref_id')->label('Ref. ID')->placeholder('-'),
            TextEntry::make('occurred_at')->label('Terjadi Pada')->dateTime(),

            // Ringkasan buku besar (hitung real-time dari ledger_entries)
            TextEntry::make('totals.debit')
                ->label('Total Debit')
                ->formatStateUsing(fn ($state, LedgerTransaction $record) => number_format((float) $record->ledger_entries()->where('direction', 'debit')->sum('amount'), 2)
                ),
            TextEntry::make('totals.credit')
                ->label('Total Credit')
                ->formatStateUsing(fn ($state, LedgerTransaction $record) => number_format((float) $record->ledger_entries()->where('direction', 'credit')->sum('amount'), 2)
                ),
            TextEntry::make('created_at')->label('Dibuat')->dateTime()->placeholder('-'),
            TextEntry::make('updated_at')->label('Diubah')->dateTime()->placeholder('-'),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('type')->label('Tipe')->badge()->sortable(),
                TextColumn::make('status')->label('Status')->badge()->sortable(),

                TextColumn::make('occurred_at')->label('Waktu')->dateTime()->sortable(),

                TextColumn::make('ledger_entries_count')->label('ledger_entries')->sortable(),

                // Ringkas akun yang terlibat (maks 2)
                TextColumn::make('accounts')
                    ->label('Akun')
                    ->formatStateUsing(function ($state, LedgerTransaction $record) {
                        $names = $record->ledger_entries
                            ->take(2)
                            ->map(fn (LedgerEntry $e) => "{$e->wallet_account?->owner_type}#{$e->wallet_account?->owner_id}")
                            ->filter()
                            ->values()
                            ->all();
                        $txt = implode(' ↔ ', $names);
                        if ($record->ledger_entries_count > 2) {
                            $txt .= ' …';
                        }

                        return $txt ?: '-';
                    })
                    ->toggleable(),

                TextColumn::make('ref_type')->label('Ref. Tipe')->toggleable()->searchable(),
                TextColumn::make('ref_id')->label('Ref. ID')->toggleable()->sortable(),

                TextColumn::make('created_at')->label('Dibuat')->dateTime()->toggleable(isToggledHiddenByDefault: true)->sortable(),
                TextColumn::make('updated_at')->label('Diubah')->dateTime()->toggleable(isToggledHiddenByDefault: true)->sortable(),
            ])
            ->filters([
                SelectFilter::make('type')
                    ->label('Tipe')
                    ->options([
                        'topup' => 'Topup',
                        'purchase_hold' => 'Purchase Hold',
                        'purchase_capture' => 'Purchase Capture',
                        'refund' => 'Refund',
                        'payout' => 'Payout',
                        'withdrawal' => 'Withdrawal',
                        'reversal' => 'Reversal',
                        'fee_capture' => 'Fee Capture',
                    ]),
                SelectFilter::make('status')
                    ->label('Status')
                    ->options([
                        'pending' => 'Pending',
                        'posted' => 'Posted',
                        'void' => 'Void',
                    ]),
                // Filter Wallet Account lewat relasi ledger_entries.wallet_account
                SelectFilter::make('wallet_account_id')
                    ->label('Wallet Account')
                    ->options(fn () => WalletAccount::query()
                        ->select('id', 'owner_type', 'owner_id')
                        ->latest('id')->limit(1000)->get()
                        ->mapWithKeys(fn ($w) => [$w->id => "{$w->id} • {$w->owner_type} #{$w->owner_id}"])
                        ->toArray()
                    )
                    ->query(function (Builder $q, array $data) {
                        $value = $data['value'] ?? null;
                        if ($value) {
                            $q->whereHas('ledger_entries', fn (Builder $e) => $e->where('wallet_account_id', $value));
                        }
                    }),
                // Filter rentang tanggal occurred_at
                Filter::make('occurred_range')
                    ->label('Rentang Waktu')
                    ->form([
                        \Filament\Forms\Components\DatePicker::make('from')->label('Dari'),
                        \Filament\Forms\Components\DatePicker::make('to')->label('Sampai'),
                    ])
                    ->query(function (Builder $q, array $data) {
                        if (! empty($data['from'])) {
                            $q->whereDate('occurred_at', '>=', $data['from']);
                        }
                        if (! empty($data['to'])) {
                            $q->whereDate('occurred_at', '<=', $data['to']);
                        }
                    }),
            ])
            ->recordActions([
                ViewAction::make(),
                EditAction::make()
                    ->visible(fn (LedgerTransaction $record) => $record->status !== 'posted')
                    ->tooltip('Transaksi posted sebaiknya tidak diedit.'),
                DeleteAction::make()
                    ->requiresConfirmation()
                    ->modalHeading('Hapus Ledger Transaction')
                    ->modalDescription('Menghapus transaksi akan menghapus entri buku besar terkait. Tindakan ini mempengaruhi audit trail. Pastikan Anda paham konsekuensinya.'),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make()
                        ->requiresConfirmation()
                        ->modalHeading('Hapus Massal')
                        ->modalDescription('Hapus massal transaksi beserta entri? Pastikan tidak mengganggu audit.'),
                ]),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ManageLedgerTransactions::route('/'),
        ];
    }
}
