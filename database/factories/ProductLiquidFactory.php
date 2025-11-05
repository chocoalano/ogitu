<?php

namespace Database\Factories;

use App\Enums\FlavorFamily;
use App\Enums\IntendedDevice;
use App\Enums\ProductType;
use App\Models\Brand;
use App\Models\Category;
use App\Models\Product;
use App\Models\ProductLiquid;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Arr; // mod|pod|both
use Illuminate\Support\Str;   // fruit|drink|dessert|mint_ice|tobacco|other

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\ProductLiquid>
 */
class ProductLiquidFactory extends Factory
{
    /** @var class-string<\App\Models\ProductLiquid> */
    protected $model = ProductLiquid::class;

    /**
     * Default state.
     */
    public function definition(): array
    {
        // Buat Product induk bertipe 'liquid' langsung via Model (bukan ProductFactory),
        // agar tidak double-create subtype.
        $productId = function (): int {
            $brandId = Brand::query()->inRandomOrder()->value('id') ?? Brand::factory()->create()->id;

            // Pastikan kategori child 'freebase' atau 'salt' tersedia
            $child = Category::whereIn('slug', ['freebase', 'salt'])->inRandomOrder()->first();
            if (! $child) {
                $root = Category::where('slug', 'liquids')->first()
                    ?? Category::factory()->rootLiquids()->create();

                foreach (['freebase', 'salt'] as $slug) {
                    if (! Category::where('slug', $slug)->exists()) {
                        Category::create([
                            'parent_id' => $root->id,
                            'name' => Str::title($slug),
                            'slug' => $slug,
                            'path' => $root->path.'/'.$slug,
                            'is_age_restricted' => true,
                        ]);
                    }
                }
                $child = Category::whereIn('slug', ['freebase', 'salt'])->inRandomOrder()->first();
            }

            $name = $this->liquidProductName();

            return Product::query()->create([
                'brand_id' => $brandId,
                'primary_category_id' => $child->id,
                'type' => ProductType::LIQUID->value,
                'name' => $name,
                'slug' => Str::slug($name).'-'.$this->faker->unique()->numberBetween(1000, 9999),
                'description' => $this->faker->optional()->paragraph(),
                'is_active' => true,
                'is_age_restricted' => true,
                'specs' => null,
            ])->id;
        };

        return [
            'product_id' => $productId,
            'intended_device' => Arr::random(IntendedDevice::cases())->value, // mod|pod|both
            'flavor_family' => Arr::random(FlavorFamily::cases())->value,   // fruit|drink|dessert|mint_ice|tobacco|other
            'bottle_size_ml' => $this->faker->randomElement([30, 60]),
        ];
    }

    /* =========================
     * STATE HELPERS (RELASI)
     * ========================= */

    /** Kaitkan ke product liquid tertentu (ID atau model). Pastikan product.type = 'liquid'. */
    public function forProduct(Product|int $product): static
    {
        $id = $product instanceof Product ? $product->id : $product;

        return $this->state(fn () => ['product_id' => $id]);
    }

    /* =========================
     * STATE HELPERS (INTENDED DEVICE)
     * ========================= */

    public function forMod(): static
    {
        return $this->state(fn () => ['intended_device' => IntendedDevice::MOD->value]);
    }

    public function forPod(): static
    {
        return $this->state(fn () => ['intended_device' => IntendedDevice::POD->value]);
    }

    public function forBoth(): static
    {
        return $this->state(fn () => ['intended_device' => IntendedDevice::BOTH->value]);
    }

    /* =========================
     * STATE HELPERS (FLAVOR FAMILY)
     * ========================= */

    public function fruit(): static
    {
        return $this->state(fn () => ['flavor_family' => FlavorFamily::FRUIT->value]);
    }

    public function drink(): static
    {
        return $this->state(fn () => ['flavor_family' => FlavorFamily::DRINK->value]);
    }

    public function dessert(): static
    {
        return $this->state(fn () => ['flavor_family' => FlavorFamily::DESSERT->value]);
    }

    public function mintIce(): static
    {
        return $this->state(fn () => ['flavor_family' => FlavorFamily::MINT_ICE->value]);
    }

    public function tobacco(): static
    {
        return $this->state(fn () => ['flavor_family' => FlavorFamily::TOBACCO->value]);
    }

    public function other(): static
    {
        return $this->state(fn () => ['flavor_family' => FlavorFamily::OTHER->value]);
    }

    /* =========================
     * STATE HELPERS (BOTTLE SIZE)
     * ========================= */

    public function bottle(int $ml): static
    {
        return $this->state(fn () => ['bottle_size_ml' => max(5, $ml)]);
    }

    public function ml30(): static
    {
        return $this->bottle(30);
    }

    public function ml60(): static
    {
        return $this->bottle(60);
    }

    /* =========================
     * UTIL
     * ========================= */

    private function liquidProductName(): string
    {
        $flavor = $this->faker->randomElement([
            'Mango', 'Strawberry', 'Blueberry', 'Grape', 'Watermelon',
            'Vanilla', 'Tobacco', 'Mint', 'Latte', 'Yogurt', 'Oatmeal',
        ]);
        $suffix = $this->faker->randomElement(['Freeze', 'Cream', 'Blast', 'Ice', 'Classic', 'Velvet']);

        return "{$flavor} {$suffix}";
    }
}
