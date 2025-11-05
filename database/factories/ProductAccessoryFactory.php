<?php

namespace Database\Factories;

use App\Enums\AccessoryType;
use App\Enums\AtomizerType;
use App\Enums\ProductType;
use App\Models\Brand;
use App\Models\Category;
use App\Models\Product;
use App\Models\ProductAccessory;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\ProductAccessory>
 */
class ProductAccessoryFactory extends Factory
{
    /** @var class-string<\App\Models\ProductAccessory> */
    protected $model = ProductAccessory::class;

    public function definition(): array
    {
        // Pilih tipe aksesoris acak
        /** @var \App\Enums\AccessoryType $accType */
        $accType = Arr::random(AccessoryType::cases());

        // Buat product induk (langsung via model, BUKAN ProductFactory) agar tidak auto-bikin subtype
        $productId = function (): int {
            $brandId = Brand::query()->inRandomOrder()->value('id') ?? Brand::factory()->create()->id;

            // Cari kategori yang path-nya mengandung 'accessories', kalau tidak ada buat root 'Accessories'
            $category = Category::query()
                ->where('path', 'like', 'accessories%')
                ->inRandomOrder()
                ->first();

            if (! $category) {
                $category = Category::factory()->rootAccessories()->create();
            }

            $name = 'Accessory '.Str::title($this->faker->words(2, true));

            return Product::query()->create([
                'brand_id' => $brandId,
                'primary_category_id' => $category->id,
                'type' => ProductType::ACCESSORY->value,
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
            'accessory_type' => $accType->value,                                   // enum AccessoryType
            'atomizer_type' => in_array($accType->value, ['atomizer', 'tank'], true)
                                ? Arr::random(AtomizerType::cases())->value       // rda|rta|rdta|tank
                                : null,
        ];
    }

    /* =========================
     * STATE HELPERS
     * ========================= */

    /** Kaitkan ke product tertentu (pastikan product.type = 'accessory') */
    public function forProduct(Product|int $product): static
    {
        $id = $product instanceof Product ? $product->id : $product;

        return $this->state(fn () => ['product_id' => $id]);
    }

    public function atomizer(?AtomizerType $type = null): static
    {
        return $this->state(fn () => [
            'accessory_type' => AccessoryType::ATOMIZER->value,
            'atomizer_type' => ($type?->value) ?? Arr::random(AtomizerType::cases())->value,
        ]);
    }

    public function tank(): static
    {
        return $this->state(fn () => [
            'accessory_type' => AccessoryType::TANK->value,
            'atomizer_type' => Arr::random(AtomizerType::cases())->value, // izinkan 'tank' juga sesuai enum
        ]);
    }

    public function cartridge(): static
    {
        return $this->state(fn () => [
            'accessory_type' => AccessoryType::CARTRIDGE->value,
            'atomizer_type' => null,
        ]);
    }

    public function coil(): static
    {
        return $this->state(fn () => [
            'accessory_type' => AccessoryType::COIL->value,
            'atomizer_type' => null,
        ]);
    }

    public function cotton(): static
    {
        return $this->state(fn () => [
            'accessory_type' => AccessoryType::COTTON->value,
            'atomizer_type' => null,
        ]);
    }

    public function battery(): static
    {
        return $this->state(fn () => [
            'accessory_type' => AccessoryType::BATTERY->value,
            'atomizer_type' => null,
        ]);
    }

    public function charger(): static
    {
        return $this->state(fn () => [
            'accessory_type' => AccessoryType::CHARGER->value,
            'atomizer_type' => null,
        ]);
    }

    public function tools(): static
    {
        return $this->state(fn () => [
            'accessory_type' => AccessoryType::TOOLS->value,
            'atomizer_type' => null,
        ]);
    }

    public function replacementPod(): static
    {
        return $this->state(fn () => [
            'accessory_type' => AccessoryType::REPLACEMENT_POD->value,
            'atomizer_type' => null,
        ]);
    }

    /** Set atomizer_type spesifik (hanya efek jika accessory_type = atomizer/tank) */
    public function atomizerType(AtomizerType|string $type): static
    {
        $val = $type instanceof AtomizerType ? $type->value : (string) $type;

        return $this->state(fn () => ['atomizer_type' => $val]);
    }
}
