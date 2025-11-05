<?php

namespace Database\Factories;

use App\Enums\ListingStatus;
use App\Enums\ProductType;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\Shop;
use App\Models\VendorListing;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Arr;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\VendorListing>
 */
class VendorListingFactory extends Factory
{
    /** @var class-string<\App\Models\VendorListing> */
    protected $model = VendorListing::class;

    /**
     * Default state.
     */
    public function definition(): array
    {
        $variant = ProductVariant::inRandomOrder()->first() ?? ProductVariant::factory()->create();
        $shop = Shop::inRandomOrder()->first() ?? Shop::factory()->create();

        $productType = Product::query()->whereKey($variant->product_id)->value('type');

        // Harga dasar disesuaikan tipe produk
        $basePrice = match ($productType) {
            ProductType::DEVICE->value => $this->faker->numberBetween(300_000, 1_200_000),
            ProductType::LIQUID->value => $this->faker->numberBetween(65_000, 180_000),
            default /* ACCESSORY */ => $this->faker->numberBetween(30_000, 300_000),
        };

        $hasPromo = $this->faker->boolean(30);
        $promoPrice = $hasPromo ? $this->faker->numberBetween((int) ($basePrice * 0.75), (int) ($basePrice * 0.95)) : null;
        $promoEndsAt = $hasPromo ? $this->faker->optional(0.7)->dateTimeBetween('+3 days', '+21 days') : null;

        return [
            'shop_id' => $shop->id,
            'product_variant_id' => $variant->id,
            'condition' => $this->faker->boolean(90) ? 'new' : 'refurbished', // 'new'|'refurbished'
            'price' => $basePrice,
            'promo_price' => $promoPrice,
            'promo_ends_at' => $promoEndsAt,
            'qty_available' => $this->faker->numberBetween(0, 120),
            'min_order_qty' => 1,
            'status' => Arr::random(ListingStatus::cases())->value, // active|inactive|out_of_stock|banned
        ];
    }

    /* =========================
     * RELATION HELPERS
     * ========================= */

    public function forShop(Shop|int $shop): static
    {
        return $this->state(fn () => [
            'shop_id' => $shop instanceof Shop ? $shop->id : $shop,
        ]);
    }

    public function forVariant(ProductVariant|int $variant): static
    {
        return $this->state(fn () => [
            'product_variant_id' => $variant instanceof ProductVariant ? $variant->id : $variant,
        ]);
    }

    /* =========================
     * STATUS HELPERS
     * ========================= */

    public function active(): static
    {
        return $this->state(fn () => ['status' => ListingStatus::ACTIVE->value]);
    }

    public function inactive(): static
    {
        return $this->state(fn () => ['status' => ListingStatus::INACTIVE->value]);
    }

    public function outOfStock(): static
    {
        return $this->state(fn () => [
            'status' => ListingStatus::OUT_OF_STOCK->value,
            'qty_available' => 0,
        ]);
    }

    public function banned(): static
    {
        return $this->state(fn () => ['status' => ListingStatus::BANNED->value]);
    }

    /* =========================
     * ATTR HELPERS
     * ========================= */

    public function price(int|float $price): static
    {
        return $this->state(fn () => ['price' => max(1, (int) $price)]);
    }

    public function promo(int|float $promoPrice, \DateTimeInterface|string|null $endsAt = null): static
    {
        return $this->state(function (array $attr) use ($promoPrice, $endsAt) {
            return [
                'promo_price' => max(1, (int) $promoPrice),
                'promo_ends_at' => $endsAt ? ($endsAt instanceof \DateTimeInterface ? $endsAt : now()->parse($endsAt)) : ($attr['promo_ends_at'] ?? now()->addDays(7)),
            ];
        });
    }

    /** Diskon persen dari price saat ini (mis. 20 → 20%) */
    public function promoPercent(int $percent, \DateTimeInterface|string|null $endsAt = null): static
    {
        $percent = max(1, min(95, $percent));

        return $this->state(function (array $attr) use ($percent, $endsAt) {
            $price = (int) ($attr['price'] ?? 0);
            $promo = max(1, (int) round($price * (100 - $percent) / 100));

            return [
                'promo_price' => $promo,
                'promo_ends_at' => $endsAt ? ($endsAt instanceof \DateTimeInterface ? $endsAt : now()->parse($endsAt)) : ($attr['promo_ends_at'] ?? now()->addDays(7)),
            ];
        });
    }

    public function noPromo(): static
    {
        return $this->state(fn () => ['promo_price' => null, 'promo_ends_at' => null]);
    }

    public function stock(int $qty): static
    {
        return $this->state(fn () => ['qty_available' => max(0, $qty)]);
    }

    public function minOrder(int $qty): static
    {
        return $this->state(fn () => ['min_order_qty' => max(1, $qty)]);
    }

    public function conditionNew(): static
    {
        return $this->state(fn () => ['condition' => 'new']);
    }

    public function conditionUsed(): static
    {
        return $this->state(fn () => ['condition' => 'used']);
    }
}
