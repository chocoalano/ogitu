<?php

namespace Database\Factories;

use App\Models\Customer;
use App\Models\Shop;
use App\Models\WalletAccount;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Arr;

// (opsional) jika kamu punya model Escrow:
// use App\Models\Escrow;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\WalletAccount>
 */
class WalletAccountFactory extends Factory
{
    /** @var class-string<\App\Models\WalletAccount> */
    protected $model = WalletAccount::class;

    /**
     * Default state.
     *
     * Kolom schema:
     * - owner_type: 'customer'|'shop'|'platform'|'escrow'
     * - owner_id  : bigint
     * - currency  : char(3) default 'IDR'
     * - balance   : decimal(18,2) default 0.00
     * - status    : 'active'|'suspended' default 'active'
     */
    public function definition(): array
    {
        // Fokus ke pemilik nyata (customer/shop). 'platform' / 'escrow' diset dengan helper agar lebih terkontrol.
        $ownerType = Arr::random(['customer', 'shop']);

        $ownerId = match ($ownerType) {
            'customer' => Customer::query()->inRandomOrder()->value('id') ?? Customer::factory(),
            'shop' => Shop::query()->inRandomOrder()->value('id') ?? Shop::factory(),
            default => 0, // fallback aman
        };

        return [
            'owner_type' => $ownerType,
            'owner_id' => $ownerId,
            'currency' => 'IDR',
            'balance' => 0.00, // sumber kebenaran saldo = ledger; cache diisi via entry factory
            'status' => 'active',
        ];
    }

    /* =========================
     * STATE HELPERS — OWNER
     * ========================= */

    /** Set pemilik akun: Customer tertentu (model/ID) */
    public function forCustomer(Customer|int $customer): static
    {
        return $this->state(fn () => [
            'owner_type' => 'customer',
            'owner_id' => $customer instanceof Customer ? $customer->id : $customer,
        ]);
    }

    /** Set pemilik akun: Shop tertentu (model/ID) */
    public function forShop(Shop|int $shop): static
    {
        return $this->state(fn () => [
            'owner_type' => 'shop',
            'owner_id' => $shop instanceof Shop ? $shop->id : $shop,
        ]);
    }

    /** Set akun platform (owner_id umum dipakai 0) */
    public function platform(int $ownerId = 0): static
    {
        return $this->state(fn () => [
            'owner_type' => 'platform',
            'owner_id' => $ownerId,
        ]);
    }

    /**
     * Set akun escrow (biasanya owner_id = 0 dahulu,
     * lalu di-update oleh EscrowFactory->afterCreating()).
     */
    public function forEscrow(int $escrowId = 0 /* | Escrow $escrow */): static
    {
        // Jika kamu ingin menerima model Escrow:
        // $id = $escrow instanceof Escrow ? $escrow->id : $escrowId;
        $id = $escrowId;

        return $this->state(fn () => [
            'owner_type' => 'escrow',
            'owner_id' => $id, // 0 sementara; akan diisi saat escrow dibuat
        ]);
    }

    /* =========================
     * STATE HELPERS — ATTR
     * ========================= */

    /** Set saldo awal (cache). Disarankan 0, lalu gunakan LedgerEntryFactory untuk mutasi. */
    public function withBalance(float|int $amount): static
    {
        return $this->state(fn () => [
            'balance' => round(max(0, (float) $amount), 2),
        ]);
    }

    /** Ganti mata uang (ISO-4217 3 huruf) */
    public function currency(string $iso3): static
    {
        return $this->state(fn () => ['currency' => strtoupper(substr($iso3, 0, 3))]);
    }

    public function idr(): static
    {
        return $this->currency('IDR');
    }

    public function usd(): static
    {
        return $this->currency('USD');
    }

    public function sgd(): static
    {
        return $this->currency('SGD');
    }

    public function active(): static
    {
        return $this->state(fn () => ['status' => 'active']);
    }

    public function suspended(): static
    {
        return $this->state(fn () => ['status' => 'suspended']);
    }
}
