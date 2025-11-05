<?php

namespace Database\Factories;

use App\Enums\NicotineType;
use App\Enums\ProductType;
use App\Models\Category;
use App\Models\Product;
use App\Models\ProductVariant;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\ProductVariant>
 */
class ProductVariantFactory extends Factory
{
    /** @var class-string<\App\Models\ProductVariant> */
    protected $model = ProductVariant::class;

    /**
     * Default state.
     *
     * - Menentukan product induk (acak/baru).
     * - Menurunkan atribut varian berdasarkan tipe produk induk:
     *   * LIQUID: capacity_ml, nicotine_type, nicotine_mg, VG/PG
     *   * DEVICE: warna / puff_count (untuk disposable)
     *   * ACCESSORY: coil_resistance / capacity_ml (untuk cartridge/pod)
     */
    public function definition(): array
    {
        $product = Product::inRandomOrder()->first() ?? Product::factory()->create();

        // Basis data varian umum
        $base = [
            'product_id' => $product->id,
            'sku' => strtoupper($this->faker->bothify('SKU-####-???')),
            'barcode' => $this->faker->optional(0.6)->ean13(),
            'name' => $this->variantNameFromProduct($product),
            'is_active' => true,
            'specs' => null,
        ];

        // Turunan field khusus per tipe produk
        return match ($product->type) {
            ProductType::LIQUID->value => $base + $this->liquidFields(),
            ProductType::DEVICE->value => $base + $this->deviceFields($product),
            ProductType::ACCESSORY->value => $base + $this->accessoryFields(),
            default => $base, // fallback aman
        };
    }

    /* ============================================================
     *  STATE HELPERS — RELASI
     * ============================================================ */

    /** Kaitkan ke product tertentu (ID atau model) */
    public function forProduct(Product|int $product): static
    {
        $id = $product instanceof Product ? $product->id : $product;

        return $this->state(fn () => ['product_id' => $id]);
    }

    /** Override nama varian */
    public function withName(string $name): static
    {
        return $this->state(fn () => [
            'name' => $name,
            'sku' => strtoupper(Str::slug($name, '-').'-'.$this->faker->unique()->numberBetween(100, 999)),
        ]);
    }

    /** Aktif/nonaktif */
    public function active(): static
    {
        return $this->state(fn () => ['is_active' => true]);
    }

    public function inactive(): static
    {
        return $this->state(fn () => ['is_active' => false]);
    }

    /** Set SKU / barcode spesifik */
    public function withSku(string $sku): static
    {
        return $this->state(fn () => ['sku' => strtoupper($sku)]);
    }

    public function withBarcode(?string $barcode): static
    {
        return $this->state(fn () => ['barcode' => $barcode]);
    }

    /* ============================================================
     *  STATE HELPERS — LIQUID
     * ============================================================ */

    /** Varian liquid Freebase (VG/PG default 70/30, mg rendah) */
    public function liquidFreebase(?int $mg = null, ?int $ml = null): static
    {
        $mg = $mg ?? Arr::random([3, 6]);
        $ml = $ml ?? Arr::random([30, 60]);

        return $this->state(fn () => [
            'capacity_ml' => $ml,
            'nicotine_type' => NicotineType::FREEBASE->value,
            'nicotine_mg' => $mg,
            'vg_ratio' => 70,
            'pg_ratio' => 30,
            'puff_count' => null,
            'color' => null,
            'coil_resistance_ohm' => null,
        ]);
    }

    /** Varian liquid Salt (VG/PG default 50/50, mg tinggi) */
    public function liquidSalt(?int $mg = null, ?int $ml = null): static
    {
        $mg = $mg ?? Arr::random([25, 30]);
        $ml = $ml ?? Arr::random([30, 60]);

        return $this->state(fn () => [
            'capacity_ml' => $ml,
            'nicotine_type' => NicotineType::SALT->value,
            'nicotine_mg' => $mg,
            'vg_ratio' => 50,
            'pg_ratio' => 50,
            'puff_count' => null,
            'color' => null,
            'coil_resistance_ohm' => null,
        ]);
    }

    /* ============================================================
     *  STATE HELPERS — DEVICE
     * ============================================================ */

    /** Varian device warna tertentu */
    public function deviceColor(string $color): static
    {
        return $this->state(fn () => [
            'color' => $color,
            'capacity_ml' => null,
            'nicotine_type' => null,
            'nicotine_mg' => null,
            'vg_ratio' => null,
            'pg_ratio' => null,
            'coil_resistance_ohm' => null,
        ]);
    }

    /** Varian disposable dengan estimasi puff count */
    public function disposablePuffs(int $puffs): static
    {
        return $this->state(fn () => [
            'puff_count' => max(100, $puffs),
            'capacity_ml' => null,
            'nicotine_type' => null,
            'nicotine_mg' => null,
            'vg_ratio' => null,
            'pg_ratio' => null,
            'coil_resistance_ohm' => null,
        ]);
    }

    /* ============================================================
     *  STATE HELPERS — ACCESSORY
     * ============================================================ */

    /** Varian coil dengan resistansi */
    public function coilResistance(float $ohm): static
    {
        return $this->state(fn () => [
            'coil_resistance_ohm' => round(max(0.05, $ohm), 2),
            'capacity_ml' => null,
            'nicotine_type' => null,
            'nicotine_mg' => null,
            'vg_ratio' => null,
            'pg_ratio' => null,
            'puff_count' => null,
        ]);
    }

    /** Varian cartridge/pod dengan kapasitas ml */
    public function podCapacity(float $ml): static
    {
        return $this->state(fn () => [
            'capacity_ml' => round(max(0.5, $ml), 2), // 2.0–3.0 ml umum
            'nicotine_type' => null,
            'nicotine_mg' => null,
            'vg_ratio' => null,
            'pg_ratio' => null,
            'puff_count' => null,
            'coil_resistance_ohm' => null,
        ]);
    }

    /* ============================================================
     *  UTIL INTERNAL
     * ============================================================ */

    /** Nama varian default yang “masuk akal” berdasar tipe produk */
    private function variantNameFromProduct(Product $product): string
    {
        return match ($product->type) {
            ProductType::LIQUID->value => $product->name.' '.Arr::random(['3mg', '6mg', '25mg', '30mg']),
            ProductType::DEVICE->value => $product->name.' '.Arr::random(['Black', 'Silver', 'Blue', 'Red']),
            ProductType::ACCESSORY->value => $product->name.' '.Arr::random(['0.3Ω', '0.6Ω', '0.8Ω', 'Pod 2ml']),
            default => $product->name.' Var '.$this->faker->randomElement(['A', 'B', 'C']),
        };
    }

    /** Field khusus varian liquid */
    private function liquidFields(): array
    {
        $nicType = Arr::random([NicotineType::FREEBASE->value, NicotineType::SALT->value]);
        $isSalt = $nicType === NicotineType::SALT->value;

        return [
            'capacity_ml' => Arr::random([30, 60]),
            'nicotine_type' => $nicType,
            'nicotine_mg' => $isSalt ? Arr::random([25, 30]) : Arr::random([3, 6]),
            'vg_ratio' => $isSalt ? 50 : 70,
            'pg_ratio' => $isSalt ? 50 : 30,
            'puff_count' => null,
            'color' => null,
            'coil_resistance_ohm' => null,
        ];
    }

    /** Field khusus varian device (warna & puff untuk disposable) */
    private function deviceFields(Product $product): array
    {
        // Jika kategori mengandung 'disposable' → berikan puff_count
        $isDisposable = false;
        if ($product->relationLoaded('category')) {
            $isDisposable = str_contains(strtolower($product->category?->slug ?? ''), 'disposable');
        } else {
            // cek via kategori
            $cat = Category::find($product->primary_category_id);
            $isDisposable = str_contains(strtolower($cat?->slug ?? ''), 'disposable');
        }

        return [
            'capacity_ml' => null,
            'nicotine_type' => null,
            'nicotine_mg' => null,
            'vg_ratio' => null,
            'pg_ratio' => null,
            'puff_count' => $isDisposable ? $this->faker->numberBetween(600, 10000) : null,
            'color' => $this->faker->randomElement(['Black', 'Silver', 'Blue', 'Red', 'Gunmetal', 'Green']),
            'coil_resistance_ohm' => null,
        ];
    }

    /** Field khusus varian accessory (coil/pod) */
    private function accessoryFields(): array
    {
        // 50% coil (dengan resistansi), 30% pod capacity, 20% lainnya (warna)
        $pick = $this->faker->numberBetween(1, 10);
        if ($pick <= 5) {
            return [
                'capacity_ml' => null,
                'nicotine_type' => null,
                'nicotine_mg' => null,
                'vg_ratio' => null,
                'pg_ratio' => null,
                'puff_count' => null,
                'color' => null,
                'coil_resistance_ohm' => $this->faker->randomFloat(2, 0.2, 1.2),
            ];
        } elseif ($pick <= 8) {
            return [
                'capacity_ml' => $this->faker->randomElement([2.0, 2.5, 3.0]),
                'nicotine_type' => null,
                'nicotine_mg' => null,
                'vg_ratio' => null,
                'pg_ratio' => null,
                'puff_count' => null,
                'color' => null,
                'coil_resistance_ohm' => null,
            ];
        }

        return [
            'capacity_ml' => null,
            'nicotine_type' => null,
            'nicotine_mg' => null,
            'vg_ratio' => null,
            'pg_ratio' => null,
            'puff_count' => null,
            'color' => $this->faker->optional(0.5)->safeColorName(),
            'coil_resistance_ohm' => null,
        ];
    }
}
