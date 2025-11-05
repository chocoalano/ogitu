<?php

namespace Database\Factories;

use App\Enums\ShopStatus;
use App\Models\Address;
use App\Models\Shop;
use App\Models\Vendor;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Shop>
 */
class ShopFactory extends Factory
{
    /** @var class-string<\App\Models\Shop> */
    protected $model = Shop::class;

    /**
     * Default state.
     */
    public function definition(): array
    {
        $name = rtrim($this->faker->unique()->company(), '.');

        return [
            'vendor_id' => Vendor::factory(),
            'name' => $name,
            'slug' => Str::slug($name).'-'.$this->faker->unique()->numberBetween(100, 999),
            'description' => $this->faker->sentence(12),
            'pickup_address_id' => null,            // set via ->withPickupAddress() bila perlu
            'rating_avg' => 0,               // 0..5 (update lewat review aggregator)
            'status' => ShopStatus::OPEN->value, // 'open'|'closed'|'suspended'
        ];
    }

    /* =========================
     * STATE HELPERS — STATUS
     * ========================= */

    public function open(): static
    {
        return $this->state(fn () => ['status' => ShopStatus::OPEN->value]);
    }

    public function closed(): static
    {
        return $this->state(fn () => ['status' => ShopStatus::CLOSED->value]);
    }

    public function suspended(): static
    {
        return $this->state(fn () => ['status' => ShopStatus::SUSPENDED->value]);
    }

    /* =========================
     * STATE HELPERS — RELASI & ATRIBUT
     * ========================= */

    /** Tautkan ke vendor tertentu (model/ID) */
    public function forVendor(Vendor|int $vendor): static
    {
        return $this->state(fn () => [
            'vendor_id' => $vendor instanceof Vendor ? $vendor->id : $vendor,
        ]);
    }

    /** Set alamat pickup (model/ID) */
    public function withPickupAddress(Address|int $address): static
    {
        return $this->state(fn () => [
            'pickup_address_id' => $address instanceof Address ? $address->id : $address,
        ]);
    }

    /** Set rating rata-rata (0..5, dibulatkan 2 desimal) */
    public function withRating(float $avg): static
    {
        $avg = max(0, min(5, $avg));

        return $this->state(fn () => ['rating_avg' => round($avg, 2)]);
    }

    /** Override nama + slug sekaligus */
    public function withName(string $name): static
    {
        return $this->state(fn () => [
            'name' => $name,
            'slug' => Str::slug($name).'-'.$this->faker->unique()->numberBetween(100, 999),
        ]);
    }

    /** Deskripsi khusus */
    public function withDescription(string $desc): static
    {
        return $this->state(fn () => ['description' => $desc]);
    }
}
