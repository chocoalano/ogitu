<?php

namespace Database\Factories;

use App\Enums\LedgerStatus;
use App\Enums\LedgerTransactionType;
use App\Models\Customer;
use App\Models\LedgerTransaction;
use App\Models\OrderShop;
use App\Models\Shop;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\LedgerTransaction>
 */
class LedgerTransactionFactory extends Factory
{
    /** @var class-string<\App\Models\LedgerTransaction> */
    protected $model = LedgerTransaction::class;

    /**
     * Default state.
     */
    public function definition(): array
    {
        $type = $this->faker->randomElement([
            LedgerTransactionType::TOPUP->value,
            LedgerTransactionType::PURCHASE_HOLD->value,
            LedgerTransactionType::PURCHASE_CAPTURE->value,
            LedgerTransactionType::REFUND->value,
            LedgerTransactionType::PAYOUT->value,
            LedgerTransactionType::WITHDRAWAL->value,
            LedgerTransactionType::REVERSAL->value,
            LedgerTransactionType::FEE_CAPTURE->value,
        ]);

        // 70% posted, sisanya pending/void
        $status = $this->faker->randomElement([
            LedgerStatus::POSTED->value,
            LedgerStatus::POSTED->value,
            LedgerStatus::POSTED->value,
            LedgerStatus::PENDING->value,
            LedgerStatus::VOID->value,
        ]);

        return [
            'type' => $type,
            'status' => $status,
            'ref_type' => null, // gunakan state helper forOrderShop()/forReferenceKey() bila perlu
            'ref_id' => null,
            'occurred_at' => Carbon::now()
                ->subDays($this->faker->numberBetween(0, 30))
                ->subMinutes($this->faker->numberBetween(0, 1440)),
        ];
    }

    /* =========================
     * STATES: TYPE
     * ========================= */

    public function topup(): static
    {
        return $this->state(fn () => ['type' => LedgerTransactionType::TOPUP->value]);
    }

    public function purchaseHold(): static
    {
        return $this->state(fn () => ['type' => LedgerTransactionType::PURCHASE_HOLD->value]);
    }

    public function purchaseCapture(): static
    {
        return $this->state(fn () => ['type' => LedgerTransactionType::PURCHASE_CAPTURE->value]);
    }

    public function refund(): static
    {
        return $this->state(fn () => ['type' => LedgerTransactionType::REFUND->value]);
    }

    public function payout(): static
    {
        return $this->state(fn () => ['type' => LedgerTransactionType::PAYOUT->value]);
    }

    public function withdrawal(): static
    {
        return $this->state(fn () => ['type' => LedgerTransactionType::WITHDRAWAL->value]);
    }

    public function reversal(): static
    {
        return $this->state(fn () => ['type' => LedgerTransactionType::REVERSAL->value]);
    }

    public function feeCapture(): static
    {
        return $this->state(fn () => ['type' => LedgerTransactionType::FEE_CAPTURE->value]);
    }

    /* =========================
     * STATES: STATUS
     * ========================= */

    public function posted(): static
    {
        return $this->state(fn () => ['status' => LedgerStatus::POSTED->value]);
    }

    public function pending(): static
    {
        return $this->state(fn () => ['status' => LedgerStatus::PENDING->value]);
    }

    public function void(): static
    {
        return $this->state(fn () => ['status' => LedgerStatus::VOID->value]);
    }

    /* =========================
     * STATES: REFERENCE (butuh morphMap sesuai)
     * ========================= */

    /** Referensi ke sub-order toko (pastikan AppServiceProvider::morphMap ada key 'order_shop') */
    public function forOrderShop(OrderShop|int $orderShop): static
    {
        return $this->state(fn () => [
            'ref_type' => 'order_shop',
            'ref_id' => $orderShop instanceof OrderShop ? $orderShop->id : $orderShop,
        ]);
    }

    /** Referensi generik: gunakan key morphMap (mis. 'customer', 'shop', 'order_shop') */
    public function forReferenceKey(string $morphKey, int $id): static
    {
        return $this->state(fn () => [
            'ref_type' => $morphKey,
            'ref_id' => $id,
        ]);
    }

    /** Contoh helper lain (opsional) */
    public function forShop(Shop|int $shop): static
    {
        return $this->forReferenceKey('shop', $shop instanceof Shop ? $shop->id : $shop);
    }

    public function forCustomer(Customer|int $customer): static
    {
        return $this->forReferenceKey('customer', $customer instanceof Customer ? $customer->id : $customer);
    }

    /* =========================
     * STATES: WAKTU
     * ========================= */

    /** Set occurred_at spesifik */
    public function occurredAt(\DateTimeInterface|string $when): static
    {
        return $this->state(fn () => [
            'occurred_at' => $when instanceof \DateTimeInterface ? Carbon::instance($when) : Carbon::parse($when),
        ]);
    }

    /** occurred_at dalam rentang relatif hari terakhir */
    public function withinLastDays(int $days = 7): static
    {
        $days = max(0, $days);

        return $this->state(fn () => [
            'occurred_at' => Carbon::now()
                ->subDays($this->faker->numberBetween(0, $days))
                ->subMinutes($this->faker->numberBetween(0, 1440)),
        ]);
    }
}
