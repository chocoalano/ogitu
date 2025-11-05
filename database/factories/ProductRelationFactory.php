<?php

namespace Database\Factories;

use App\Models\Product;
use App\Models\ProductRelation;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Arr;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\ProductRelation>
 */
class ProductRelationFactory extends Factory
{
    /** @var class-string<\App\Models\ProductRelation> */
    protected $model = ProductRelation::class;

    /** Daftar tipe yang valid sesuai ENUM kolom */
    private const TYPES = [
        'compatible_with',   // simetris
        'recommended_with',  // simetris
        'uses',              // asimetris (A uses B)
        'replacement_for',   // asimetris (A replacement_for B)
    ];

    /**
     * Default state: pilih 2 produk berbeda + tipe relasi valid.
     */
    public function definition(): array
    {
        $a = Product::inRandomOrder()->first() ?? Product::factory()->create();

        // pastikan produk berbeda
        do {
            $b = Product::inRandomOrder()->first() ?? Product::factory()->create();
        } while ($b->id === $a->id);

        return [
            'product_id' => $a->id,
            'related_product_id' => $b->id,
            'relation_type' => Arr::random(self::TYPES),
        ];
    }

    /* =========================
     * STATE HELPERS — RELASI
     * ========================= */

    /** Set pasangan produk eksplisit (ID atau model) */
    public function forProducts(Product|int $product, Product|int $related): static
    {
        $a = $product instanceof Product ? $product->id : $product;
        $b = $related instanceof Product ? $related->id : $related;

        if ($a === $b) {
            $alt = Product::whereKeyNot($a)->inRandomOrder()->value('id') ?? Product::factory()->create()->id;
            $b = $alt;
        }

        return $this->state(fn () => [
            'product_id' => $a,
            'related_product_id' => $b,
        ]);
    }

    /** Setter tipe generik (harus salah satu dari self::TYPES) */
    public function type(string $type): static
    {
        if (! in_array($type, self::TYPES, true)) {
            throw new \InvalidArgumentException("Invalid relation_type: {$type}");
        }

        return $this->state(fn () => ['relation_type' => $type]);
    }

    /** Tipe helper yang sesuai ENUM */
    public function compatibleWith(): static
    {
        return $this->type('compatible_with');
    }

    public function recommendedWith(): static
    {
        return $this->type('recommended_with');
    }

    public function uses(): static
    {
        return $this->type('uses');
    }

    public function replacementFor(): static
    {
        return $this->type('replacement_for');
    }

    /** Alias (kompatibilitas lama): accessoryFor() → uses() */
    public function accessoryFor(): static
    {
        return $this->uses();
    }

    /**
     * Buat relasi dua arah hanya untuk tipe SIMETRIS:
     * - compatible_with
     * - recommended_with
     */
    public function bidirectional(): static
    {
        return $this->afterCreating(function (ProductRelation $rel) {
            if (in_array($rel->relation_type, ['compatible_with', 'recommended_with'], true)) {
                $existsReverse = ProductRelation::query()
                    ->where('product_id', $rel->related_product_id)
                    ->where('related_product_id', $rel->product_id)
                    ->where('relation_type', $rel->relation_type)
                    ->exists();

                if (! $existsReverse) {
                    ProductRelation::create([
                        'product_id' => $rel->related_product_id,
                        'related_product_id' => $rel->product_id,
                        'relation_type' => $rel->relation_type,
                    ]);
                }
            }
        });
    }

    /**
     * Cegah duplikasi (A,B,type) saat seeding massal.
     */
    public function preventDuplicates(): static
    {
        return $this->afterMaking(function (ProductRelation $rel) {
            $tries = 0;
            while (
                ProductRelation::query()
                    ->where('product_id', $rel->product_id)
                    ->where('related_product_id', $rel->related_product_id)
                    ->where('relation_type', $rel->relation_type)
                    ->exists()
                && $tries < 5
            ) {
                $a = Product::inRandomOrder()->first() ?? Product::factory()->create();
                do {
                    $b = Product::inRandomOrder()->first() ?? Product::factory()->create();
                } while ($b->id === $a->id);
                $rel->product_id = $a->id;
                $rel->related_product_id = $b->id;
                $rel->relation_type = Arr::random(self::TYPES);
                $tries++;
            }
        });
    }
}
