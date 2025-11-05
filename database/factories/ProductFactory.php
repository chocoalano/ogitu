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
use Illuminate\Support\Arr;
use Illuminate\Support\Str;

// NOTE: gunakan factory dari file lain yang sudah kamu buat

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Product>
 */
class ProductFactory extends Factory
{
    /** @var class-string<\App\Models\Product> */
    protected $model = Product::class;

    /**
     * Default state.
     */
    public function definition(): array
    {
        // Distribusi tipe (lebih banyak liquid & device)
        /** @var \App\Enums\ProductType $type */
        $type = Arr::random([
            ProductType::LIQUID, ProductType::LIQUID,
            ProductType::DEVICE, ProductType::DEVICE,
            ProductType::ACCESSORY,
        ]);

        $brandId = Brand::query()->inRandomOrder()->value('id') ?? Brand::factory()->create()->id;
        $categoryId = $this->pickCategoryIdByType($type);

        // Nama produk yang terasa “vape world”
        $name = match ($type) {
            ProductType::DEVICE => $this->deviceName(),
            ProductType::LIQUID => $this->liquidName(),
            ProductType::ACCESSORY => $this->accessoryName(),
        };

        return [
            'brand_id' => $brandId,
            'primary_category_id' => $categoryId,
            'type' => $type->value, // 'device'|'liquid'|'accessory'
            'name' => $name,
            'slug' => Str::slug($name).'-'.$this->faker->unique()->numberBetween(1000, 9999),
            'description' => $this->faker->optional()->paragraph(),
            'is_active' => true,
            'is_age_restricted' => true,
            'specs' => null,
        ];
    }

    /**
     * After creating: buat record subtype sesuai tipe.
     */
    public function configure(): static
    {
        return $this->afterCreating(function (Product $product) {
            switch ($product->type) {
                case ProductType::DEVICE->value:
                    // Pakai ProductDeviceFactory agar spek realistis (baterai, watt, charger)
                    ProductDeviceFactory::new()->forProduct($product)->create();
                    break;

                case ProductType::LIQUID->value:
                    // Buat liquid langsung (tidak butuh factory terpisah)
                    ProductLiquid::create([
                        'product_id' => $product->id,
                        'intended_device' => Arr::random(IntendedDevice::cases())->value, // mod|pod|both
                        'flavor_family' => Arr::random(FlavorFamily::cases())->value,   // fruit|drink|dessert|mint_ice|tobacco|other
                        'bottle_size_ml' => $this->faker->randomElement([30, 60]),
                    ]);
                    break;

                case ProductType::ACCESSORY->value:
                    // Pakai ProductAccessoryFactory agar atomizer_type diatur benar
                    ProductAccessoryFactory::new()->forProduct($product)->create();
                    break;
            }
        });
    }

    /* =========================
     * STATES
     * ========================= */

    public function device(): static
    {
        return $this->state(function () {
            return [
                'type' => ProductType::DEVICE->value,
                'primary_category_id' => $this->pickCategoryIdByType(ProductType::DEVICE),
                'name' => $this->deviceName(),
            ];
        });
    }

    public function liquid(): static
    {
        return $this->state(function () {
            return [
                'type' => ProductType::LIQUID->value,
                'primary_category_id' => $this->pickCategoryIdByType(ProductType::LIQUID),
                'name' => $this->liquidName(),
            ];
        });
    }

    public function accessory(): static
    {
        return $this->state(function () {
            return [
                'type' => ProductType::ACCESSORY->value,
                'primary_category_id' => $this->pickCategoryIdByType(ProductType::ACCESSORY),
                'name' => $this->accessoryName(),
            ];
        });
    }

    /** Set brand tertentu (ID atau model) */
    public function forBrand(Brand|int $brand): static
    {
        return $this->state(fn () => [
            'brand_id' => $brand instanceof Brand ? $brand->id : $brand,
        ]);
    }

    /** Set kategori tertentu (ID atau model) */
    public function forCategory(Category|int $cat): static
    {
        return $this->state(fn () => [
            'primary_category_id' => $cat instanceof Category ? $cat->id : $cat,
        ]);
    }

    public function inactive(): static
    {
        return $this->state(fn () => ['is_active' => false]);
    }

    public function ageRestricted(bool $restricted = true): static
    {
        return $this->state(fn () => ['is_age_restricted' => $restricted]);
    }

    public function withName(string $name): static
    {
        return $this->state(fn () => [
            'name' => $name,
            'slug' => Str::slug($name).'-'.$this->faker->unique()->numberBetween(1000, 9999),
        ]);
    }

    /* =========================
     * UTIL: Nama & Kategori
     * ========================= */

    private function deviceName(): string
    {
        return $this->faker->randomElement(['Foom Pod Y', 'Caliburn G4', 'VaporMax', 'Aero AIO', 'ModX', 'Pulse'])
            .' '.$this->faker->randomElement(['Pro', 'Lite', 'SE', 'X', 'Prime']);
    }

    private function liquidName(): string
    {
        $flavor = $this->faker->randomElement(['Mango', 'Strawberry', 'Blueberry', 'Grape', 'Watermelon', 'Vanilla', 'Tobacco', 'Mint', 'Latte', 'Yogurt']);
        $suffix = $this->faker->randomElement(['Freeze', 'Cream', 'Blast', 'Ice', 'Classic']);

        return "{$flavor} {$suffix}";
    }

    private function accessoryName(): string
    {
        return $this->faker->randomElement(['RDA', 'RTA', 'RDTA', 'Coil', 'Cotton', 'Battery 18650', 'Charger', 'Pod Cartridge'])
            .' '.$this->faker->randomElement(['Pro', 'X', 'V2', 'Kit']);
    }

    /**
     * Pilih/buat kategori utama sesuai tipe (prioritas child kategori).
     */
    private function pickCategoryIdByType(ProductType $type): int
    {
        $map = [
            ProductType::DEVICE->value => ['root' => 'devices',     'children' => ['mod', 'pod-system', 'pod-refillable', 'disposable', 'aio']],
            ProductType::LIQUID->value => ['root' => 'liquids',     'children' => ['freebase', 'salt']],
            ProductType::ACCESSORY->value => ['root' => 'accessories', 'children' => ['rda', 'rta', 'rdta', 'tank', 'cartridge', 'coil', 'cotton', 'battery', 'charger', 'tools', 'replacement-pod']],
        ];

        $rootSlug = $map[$type->value]['root'];
        $children = $map[$type->value]['children'];

        // Cari child yang sudah ada
        $child = Category::whereIn('slug', $children)->inRandomOrder()->first();
        if ($child) {
            return $child->id;
        }

        // Pastikan root ada
        $root = Category::where('slug', $rootSlug)->first();
        if (! $root) {
            $root = Category::create([
                'parent_id' => null,
                'name' => Str::title($rootSlug),
                'slug' => $rootSlug,
                'path' => $rootSlug,
                'is_age_restricted' => true,
            ]);
        }

        // Buat satu child acak
        $slug = Arr::random($children);
        $child = Category::create([
            'parent_id' => $root->id,
            'name' => Str::title(str_replace('-', ' ', $slug)),
            'slug' => $slug,
            'path' => $root->path.'/'.$slug,
            'is_age_restricted' => true,
        ]);

        return $child->id;
    }
}
