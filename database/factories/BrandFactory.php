<?php

namespace Database\Factories;

use App\Models\Brand;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Brand>
 */
class BrandFactory extends Factory
{
    /** @var class-string<\App\Models\Brand> */
    protected $model = Brand::class;

    /** Kumpulan brand “rasa” vape */
    private static array $DEVICE_BRANDS = [
        'Uwell', 'Voopoo', 'GeekVape', 'Vaporesso', 'SMOK', 'Aspire', 'Lost Vape', 'OXVA',
        'Dotmod', 'Vandy Vape', 'Wismec', 'Smoant', 'Hellvape', 'Innokin',
    ];

    private static array $LIQUID_BRANDS = [
        'Nasty Juice', 'Dinner Lady', 'BLVK', 'Ruthless', 'Yogi', 'Juice Head', 'Charlies',
        'Barista Brew', 'Twelve Monkeys', 'Yami Vapor', 'Cloud Niners', 'Kings Crest',
    ];

    private static array $ACCESSORY_BRANDS = [
        'Coil Master', 'Wotofo', 'COTN', 'Hohm Tech', 'Sony VTC', 'Samsung 30Q', 'Molicel',
        'XTAR', 'Nitecore', 'Efest', 'OFRF', 'GeekVape Tools',
    ];

    /** Sufiks untuk variasi nama agar unik tetap “brand-like” */
    private static array $SUFFIX = [
        'Labs', 'Vape', 'Works', 'Industries', 'Studio', 'Originals', 'Co', 'Juice', 'Brews',
    ];

    /**
     * Default state: pilih acak dari semua kategori.
     */
    public function definition(): array
    {
        $pool = array_values(array_unique(array_merge(
            self::$DEVICE_BRANDS,
            self::$LIQUID_BRANDS,
            self::$ACCESSORY_BRANDS
        )));

        $name = $this->uniqueBrandName($pool);

        return [
            'name' => $name,
            'slug' => Str::slug($name).'-'.$this->faker->unique()->numberBetween(10, 9999),
        ];
    }

    /**
     * State helper: brand untuk Device/Mod/Pod.
     */
    public function device(): static
    {
        return $this->state(function () {
            $name = $this->uniqueBrandName(self::$DEVICE_BRANDS);

            return [
                'name' => $name,
                'slug' => Str::slug($name).'-'.$this->faker->unique()->numberBetween(10, 9999),
            ];
        });
    }

    /**
     * State helper: brand untuk Liquid.
     */
    public function liquid(): static
    {
        return $this->state(function () {
            $name = $this->uniqueBrandName(self::$LIQUID_BRANDS);

            return [
                'name' => $name,
                'slug' => Str::slug($name).'-'.$this->faker->unique()->numberBetween(10, 9999),
            ];
        });
    }

    /**
     * State helper: brand untuk Accessories (coil, charger, battery, tools, dll).
     */
    public function accessory(): static
    {
        return $this->state(function () {
            $name = $this->uniqueBrandName(self::$ACCESSORY_BRANDS);

            return [
                'name' => $name,
                'slug' => Str::slug($name).'-'.$this->faker->unique()->numberBetween(10, 9999),
            ];
        });
    }

    /**
     * Generator nama brand yang unik tapi tetap natural.
     */
    private function uniqueBrandName(array $pool): string
    {
        // Ambil base dari pool
        $base = Arr::random($pool);

        // 60% tambah sufiks (Labs/Vape/Works/…)
        if ($this->faker->boolean(60)) {
            $base .= ' '.Arr::random(self::$SUFFIX);
        }

        // Untuk jaga unik di constraint unique(name), tambahkan varian ringan 50%
        if ($this->faker->boolean(50)) {
            $base .= ' '.$this->faker->randomElement(['Pro', 'X', 'SE', 'Prime', 'Elite', 'Classic']);
        }

        // 40% tambah angka romawi/angka kecil agar sangat unik tapi tetap enak dilihat
        if ($this->faker->boolean(40)) {
            $roman = $this->faker->randomElement(['II', 'III', 'IV']);
            $base .= ' '.$this->faker->randomElement([$roman, (string) $this->faker->numberBetween(2, 9)]);
        }

        // Terakhir, pastikan variasi sangat unik bila dipanggil banyak
        // (hindari bentrok unique index pada kolom name)
        if ($this->faker->boolean(35)) {
            $base .= ' '.$this->faker->unique()->bothify('##');
        }

        return trim($base);
    }
}
