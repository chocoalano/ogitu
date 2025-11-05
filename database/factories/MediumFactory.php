<?php

namespace Database\Factories;

use App\Models\Medium;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\Shop;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Medium>
 */
class MediumFactory extends Factory
{
    /** @var class-string<\App\Models\Medium> */
    protected $model = Medium::class;

    /**
     * Default state.
     */
    public function definition(): array
    {
        $type = $this->faker->randomElement(['product', 'variant', 'shop']);

        $owner = match ($type) {
            'product' => Product::inRandomOrder()->first() ?? Product::factory()->create(),
            'variant' => ProductVariant::inRandomOrder()->first() ?? ProductVariant::factory()->create(),
            'shop' => Shop::inRandomOrder()->first() ?? Shop::factory()->create(),
        };

        return [
            'owner_type' => $type,                // 'product' | 'variant' | 'shop'
            'owner_id' => $owner->id,
            'url' => $this->fakeImageUrl($type),
            'alt' => $owner->name ?? 'Image',
            'position' => $this->faker->numberBetween(0, 5),
        ];
    }

    /* =========================
     * STATE HELPERS
     * ========================= */

    /** Jadikan gambar utama (posisi 0) */
    public function primary(): static
    {
        return $this->state(fn () => ['position' => 0]);
    }

    /** Tetapkan owner ke product tertentu */
    public function forProduct(Product|int $product): static
    {
        $id = $product instanceof Product ? $product->id : $product;
        $name = $product instanceof Product ? $product->name : (Product::find($id)?->name);

        return $this->state(fn () => [
            'owner_type' => 'product',
            'owner_id' => $id,
            'alt' => $name,
        ]);
    }

    /** Tetapkan owner ke variant tertentu */
    public function forVariant(ProductVariant|int $variant): static
    {
        $id = $variant instanceof ProductVariant ? $variant->id : $variant;
        $name = $variant instanceof ProductVariant ? $variant->name : (ProductVariant::find($id)?->name);

        return $this->state(fn () => [
            'owner_type' => 'variant',
            'owner_id' => $id,
            'alt' => $name,
        ]);
    }

    /** Tetapkan owner ke shop tertentu */
    public function forShop(Shop|int $shop): static
    {
        $id = $shop instanceof Shop ? $shop->id : $shop;
        $name = $shop instanceof Shop ? $shop->name : (Shop::find($id)?->name);

        return $this->state(fn () => [
            'owner_type' => 'shop',
            'owner_id' => $id,
            'alt' => $name,
        ]);
    }

    /** Override URL gambar */
    public function withUrl(string $url): static
    {
        return $this->state(fn () => ['url' => $url]);
    }

    /** Set urutan/posisi */
    public function position(int $pos): static
    {
        return $this->state(fn () => ['position' => max(0, $pos)]);
    }

    /* =========================
     * UTIL
     * ========================= */

    protected function fakeImageUrl(string $type): string
    {
        // Placeholder resolusi acak yang stabil untuk demo/seeding
        [$w, $h] = $this->faker->randomElement([[800, 800], [1200, 800], [1000, 1000]]);

        return sprintf('https://picsum.photos/seed/%s/%d/%d', Str::random(10), $w, $h);
    }
}
