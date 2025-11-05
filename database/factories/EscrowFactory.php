<?php

namespace Database\Factories;

use App\Enums\EscrowStatus;
use App\Enums\WalletOwnerType;
use App\Models\Escrow;
use App\Models\OrderShop;
use App\Models\WalletAccount;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Escrow>
 */
class EscrowFactory extends Factory
{
    /** @var class-string<\App\Models\Escrow> */
    protected $model = Escrow::class;

    /**
     * Default state.
     */
    public function definition(): array
    {
        return [
            // NOTE: butuh OrderShopFactory jika ingin otomatis. Kalau belum ada, pakai state forOrderShop() saat seeding.
            'order_shop_id' => OrderShop::factory(),
            'wallet_account_id' => function () {
                // Buat wallet khusus escrow; owner_id sementara 0, nanti di-update di afterCreating()
                return WalletAccount::create([
                    'owner_type' => WalletOwnerType::ESCROW->value,
                    'owner_id' => 0,        // placeholder; akan di-set ke escrow.id setelah create
                    'currency' => 'IDR',
                    'balance' => 0,
                    'status' => 'active',
                ])->id;
            },
            'amount_held' => $this->faker->randomFloat(2, 50000, 1500000),
            'status' => EscrowStatus::HELD->value, // 'held'
        ];
    }

    /**
     * After creating: tautkan wallet.owner_id ke escrow.id
     */
    public function configure(): static
    {
        return $this->afterCreating(function (Escrow $escrow) {
            $wallet = WalletAccount::find($escrow->wallet_account_id);
            if ($wallet && $wallet->owner_type === WalletOwnerType::ESCROW->value && (int) $wallet->owner_id === 0) {
                $wallet->owner_id = $escrow->id;
                $wallet->save();
            }
        });
    }

    /* =========================
     * State helpers
     * ========================= */

    public function held(): static
    {
        return $this->state(fn () => ['status' => EscrowStatus::HELD->value]);
    }

    public function released(): static
    {
        return $this->state(fn () => ['status' => EscrowStatus::RELEASED->value]);
    }

    public function refunded(): static
    {
        return $this->state(fn () => ['status' => EscrowStatus::REFUNDED->value]);
    }

    public function partialReleased(): static
    {
        return $this->state(fn () => ['status' => EscrowStatus::PARTIAL_RELEASED->value]);
    }

    /** Set nominal yang ditahan */
    public function withAmount(float|int $amount): static
    {
        return $this->state(fn () => ['amount_held' => max(0, (float) $amount)]);
    }

    /** Tautkan ke sub-order toko tertentu */
    public function forOrderShop(OrderShop|int $orderShop): static
    {
        return $this->state(fn () => [
            'order_shop_id' => $orderShop instanceof OrderShop ? $orderShop->id : $orderShop,
        ]);
    }

    /** Pakai wallet tertentu (harus bertipe escrow) */
    public function withWallet(WalletAccount|int $wallet): static
    {
        return $this->state(fn () => [
            'wallet_account_id' => $wallet instanceof WalletAccount ? $wallet->id : $wallet,
        ]);
    }

    /** amount_held = 0 (mis. setelah full release) */
    public function zeroAmount(): static
    {
        return $this->state(fn () => ['amount_held' => 0]);
    }
}
