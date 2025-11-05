<?php

namespace Database\Factories;

use App\Enums\LedgerDirection;
use App\Models\LedgerEntry;
use App\Models\LedgerTransaction;
use App\Models\WalletAccount;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\LedgerEntry>
 */
class LedgerEntryFactory extends Factory
{
    /** @var class-string<\App\Models\LedgerEntry> */
    protected $model = LedgerEntry::class;

    /**
     * Default state.
     */
    public function definition(): array
    {
        $direction = $this->faker->randomElement([
            LedgerDirection::DEBIT->value,
            LedgerDirection::CREDIT->value,
        ]);

        return [
            // NOTE: jika belum punya LedgerTransactionFactory, set via state ->forTransaction()
            'ledger_transaction_id' => LedgerTransaction::factory(),
            // NOTE: jika belum punya WalletAccount, set via state ->forAccount()
            'account_id' => WalletAccount::factory(),
            'direction' => $direction,     // 'debit' | 'credit'
            'amount' => $this->faker->randomFloat(2, 1000, 2_000_000),
            'balance_after' => null,           // dihitung otomatis di afterCreating jika null
            'memo' => $this->faker->optional()->sentence(3),
            'created_at' => now()
                ->subDays($this->faker->numberBetween(0, 30))
                ->subMinutes($this->faker->numberBetween(0, 1440)),
        ];
    }

    /**
     * Hitung balance_after otomatis berdasarkan entry sebelumnya pada akun yang sama.
     * (Asumsi: akun dompet = aset, sehingga DEBIT menambah saldo, CREDIT mengurangi.)
     */
    public function configure(): static
    {
        return $this->afterCreating(function (LedgerEntry $entry) {
            if (is_null($entry->balance_after)) {
                // Cari entry sebelumnya pada akun yang sama (urut waktu & id)
                $prev = LedgerEntry::where('account_id', $entry->account_id)
                    ->where(function ($q) use ($entry) {
                        $q->where('created_at', '<', $entry->created_at)
                            ->orWhere(function ($q2) use ($entry) {
                                $q2->where('created_at', $entry->created_at)
                                    ->where('id', '<', $entry->id);
                            });
                    })
                    ->orderBy('created_at', 'desc')
                    ->orderBy('id', 'desc')
                    ->first();

                $prevBalance = $prev?->balance_after
                    ?? WalletAccount::query()->whereKey($entry->account_id)->value('balance')
                    ?? 0.0;

                $delta = ($entry->direction === LedgerDirection::DEBIT->value)
                    ? (float) $entry->amount
                    : -(float) $entry->amount;

                $entry->balance_after = round($prevBalance + $delta, 2);
                $entry->save();
            }
        });
    }

    /* =========================
     * STATE HELPERS
     * ========================= */

    /** Set transaksi terkait (ID atau model) */
    public function forTransaction(LedgerTransaction|int $txn): static
    {
        return $this->state(fn () => [
            'ledger_transaction_id' => $txn instanceof LedgerTransaction ? $txn->id : $txn,
        ]);
    }

    /** Set akun dompet terkait (ID atau model) */
    public function forAccount(WalletAccount|int $account): static
    {
        return $this->state(fn () => [
            'account_id' => $account instanceof WalletAccount ? $account->id : $account,
        ]);
    }

    /** Arah posting: debit (menambah saldo aset) */
    public function debit(float|int|null $amount = null): static
    {
        return $this->state(fn () => [
            'direction' => LedgerDirection::DEBIT->value,
            'amount' => $amount ?? $this->faker->randomFloat(2, 1000, 2_000_000),
        ]);
    }

    /** Arah posting: credit (mengurangi saldo aset) */
    public function credit(float|int|null $amount = null): static
    {
        return $this->state(fn () => [
            'direction' => LedgerDirection::CREDIT->value,
            'amount' => $amount ?? $this->faker->randomFloat(2, 1000, 2_000_000),
        ]);
    }

    /** Set nominal spesifik */
    public function amount(float|int $amount): static
    {
        return $this->state(fn () => ['amount' => max(0, (float) $amount)]);
    }

    /** Set memo/keterangan */
    public function memo(string $memo): static
    {
        return $this->state(fn () => ['memo' => $memo]);
    }

    /** Set waktu posting spesifik */
    public function at(\DateTimeInterface|string $when): static
    {
        return $this->state(fn () => [
            'created_at' => $when instanceof \DateTimeInterface ? $when : now()->parse($when),
        ]);
    }

    /**
     * Opsi: setelah membuat entry, sinkronkan cache saldo wallet_accounts.balance
     * agar sama dengan balance_after terbaru (berguna untuk demo/seeding).
     */
    public function syncAccountCache(): static
    {
        return $this->afterCreating(function (LedgerEntry $entry) {
            $account = WalletAccount::find($entry->account_id);
            if ($account) {
                // Ambil entry terbaru (berdasar created_at & id)
                $latest = LedgerEntry::where('account_id', $account->id)
                    ->orderBy('created_at', 'desc')
                    ->orderBy('id', 'desc')
                    ->first();

                if ($latest && $latest->balance_after !== null) {
                    $account->balance = $latest->balance_after;
                    $account->save();
                }
            }
        });
    }
}
