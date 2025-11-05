<?php

namespace Database\Factories;

use App\Models\Product;
use App\Models\ProductRestriction;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\ProductRestriction>
 */
class ProductRestrictionFactory extends Factory
{
    /** @var class-string<\App\Models\ProductRestriction> */
    protected $model = ProductRestriction::class;

    /**
     * Default state.
     */
    public function definition(): array
    {
        return [
            'product_id' => Product::factory(),
            'country_code' => $this->faker->randomElement(['ID', 'SG', 'MY', 'AU', 'US', 'GB']),
            'min_age' => $this->faker->randomElement([18, 21]),
        ];
    }

    /* =========================
     * STATE HELPERS
     * ========================= */

    /** Kaitkan ke product tertentu (ID atau model) */
    public function forProduct(Product|int $product): static
    {
        return $this->state(fn () => [
            'product_id' => $product instanceof Product ? $product->id : $product,
        ]);
    }

    /** Set negara spesifik (ISO-2, mis. 'ID', 'SG') */
    public function inCountry(string $countryCode): static
    {
        return $this->state(fn () => ['country_code' => strtoupper($countryCode)]);
    }

    /** Minimal usia 18 tahun */
    public function min18(): static
    {
        return $this->state(fn () => ['min_age' => 18]);
    }

    /** Minimal usia 21 tahun */
    public function min21(): static
    {
        return $this->state(fn () => ['min_age' => 21]);
    }
}
