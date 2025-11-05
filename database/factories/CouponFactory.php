<?php

namespace Database\Factories;

use App\Enums\CouponScope;
use App\Enums\CouponType;
use App\Models\Coupon;
use App\Models\Shop;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Coupon>
 */
class CouponFactory extends Factory
{
    /** @var class-string<\App\Models\Coupon> */
    protected $model = Coupon::class;

    /**
     * Default state.
     */
    public function definition(): array
    {
        // Pilih tipe diskon
        $type = $this->faker->randomElement([CouponType::PERCENT->value, CouponType::AMOUNT->value]);

        // Pilih cakupan kupon
        $appliesTo = $this->faker->boolean(70) ? CouponScope::PLATFORM->value : CouponScope::SHOP->value;

        // Nilai diskon
        $value = $type === CouponType::PERCENT->value
            ? $this->faker->numberBetween(5, 50)                  // 5–50%
            : $this->faker->numberBetween(10000, 150000);         // Rp10k–150k

        // Periode aktif opsional
        $startsAt = $this->faker->optional(0.6)->dateTimeBetween('-15 days', '+5 days');
        $endsAt = $startsAt
            ? $this->faker->optional(0.7)->dateTimeBetween($startsAt->modify('+1 day'), '+30 days')
            : null;

        // Buat kode kupon yang readable + unik
        $prefix = $this->faker->randomElement(['VAPE', 'POD', 'LIQ', 'COIL', 'MOD']);
        $mid = strtoupper($this->faker->bothify('##??'));
        $tail = $type === CouponType::PERCENT->value ? ($value.'P') : ($value >= 1000 ? intval($value / 1000).'K' : $value);
        $code = "{$prefix}-{$mid}-{$tail}";

        return [
            'code' => Str::upper($this->faker->unique()->bothify($code)),
            'type' => $type,                          // 'percent'|'amount'
            'value' => $value,                         // persen (0–100) atau nominal
            'applies_to' => $appliesTo,                     // 'platform'|'shop'
            'shop_id' => $appliesTo === CouponScope::SHOP->value
                                ? (Shop::query()->inRandomOrder()->value('id') ?? Shop::factory())
                                : null,
            'min_order' => $this->faker->optional(0.5)->numberBetween(50000, 300000),
            'max_uses' => $this->faker->optional(0.5)->numberBetween(50, 1000),
            'used' => 0,
            'starts_at' => $startsAt,
            'ends_at' => $endsAt,
            'status' => $this->faker->boolean(80) ? 'active' : 'inactive',
        ];
    }

    /* =========================
     * STATE HELPERS
     * ========================= */

    /** Diskon persen (opsional tentukan nilainya) */
    public function percent(?int $value = null): static
    {
        return $this->state(function () use ($value) {
            $v = $value ?? $this->faker->numberBetween(5, 50);

            return [
                'type' => CouponType::PERCENT->value,
                'value' => max(1, min(100, $v)),
                'code' => $this->codeFromValue(true, $v),
            ];
        });
    }

    /** Diskon nominal rupiah (opsional tentukan nilainya) */
    public function amount(?int $value = null): static
    {
        return $this->state(function () use ($value) {
            $v = $value ?? $this->faker->numberBetween(10000, 150000);

            return [
                'type' => CouponType::AMOUNT->value,
                'value' => max(1000, $v),
                'code' => $this->codeFromValue(false, $v),
            ];
        });
    }

    /** Berlaku untuk seluruh platform */
    public function platform(): static
    {
        return $this->state(fn () => ['applies_to' => CouponScope::PLATFORM->value, 'shop_id' => null]);
    }

    /** Berlaku untuk 1 toko tertentu */
    public function forShop(Shop|int $shop): static
    {
        return $this->state(fn () => [
            'applies_to' => CouponScope::SHOP->value,
            'shop_id' => $shop instanceof Shop ? $shop->id : $shop,
        ]);
    }

    /** Kupon aktif sekarang (starts_at <= now, ends_at >= now atau null) */
    public function liveNow(): static
    {
        return $this->state(function () {
            $start = now()->subDays($this->faker->numberBetween(0, 7));
            $end = $this->faker->optional(0.6)->dateTimeBetween('+1 day', '+21 days');

            return [
                'starts_at' => $start,
                'ends_at' => $end,
                'status' => 'active',
            ];
        });
    }

    /** Kupon belum mulai (masa depan) */
    public function future(): static
    {
        return $this->state(function () {
            $start = now()->addDays($this->faker->numberBetween(1, 10));
            $end = $this->faker->optional(0.7)->dateTimeBetween($start->copy()->addDay(), $start->copy()->addDays(30));

            return [
                'starts_at' => $start,
                'ends_at' => $end,
                'status' => 'inactive',
            ];
        });
    }

    /** Kupon sudah kadaluarsa */
    public function expired(): static
    {
        return $this->state(function () {
            $end = now()->subDays($this->faker->numberBetween(1, 10));
            $start = $end->copy()->subDays($this->faker->numberBetween(5, 20));

            return [
                'starts_at' => $start,
                'ends_at' => $end,
                'status' => 'inactive',
            ];
        });
    }

    /** Batasi jumlah pemakaian */
    public function withUsageLimit(int $maxUses): static
    {
        return $this->state(fn () => [
            'max_uses' => max(1, $maxUses),
            'used' => 0,
        ]);
    }

    /** Satu kali pakai */
    public function oneTimeUse(): static
    {
        return $this->withUsageLimit(1);
    }

    /** Minimal nilai order */
    public function withMinOrder(int $amount): static
    {
        return $this->state(fn () => [
            'min_order' => max(0, $amount),
        ]);
    }

    public function active(): static
    {
        return $this->state(fn () => ['status' => 'active']);
    }

    public function inactive(): static
    {
        return $this->state(fn () => ['status' => 'inactive']);
    }

    /* =========================
     * UTIL
     * ========================= */

    /** Bentuk kode kupon berdasarkan tipe nilai agar lebih informatif */
    private function codeFromValue(bool $isPercent, int $value): string
    {
        $prefix = $this->faker->randomElement(['VAPE', 'POD', 'LIQ', 'COIL', 'MOD']);
        $mid = strtoupper($this->faker->bothify('##??'));
        $tail = $isPercent
            ? ($value.'P')  // contoh: 10P
            : ($value >= 1000 ? intval($value / 1000).'K' : $value); // contoh: 50K

        return Str::upper("{$prefix}-{$mid}-{$tail}");
    }
}
