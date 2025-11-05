<?php

namespace Database\Factories;

use App\Enums\BatteryType;
use App\Enums\ChargerType;
use App\Enums\DeviceFormFactor;
use App\Enums\ProductType;
use App\Models\Brand;
use App\Models\Category;
use App\Models\Product;
use App\Models\ProductDevice;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\ProductDevice>
 */
class ProductDeviceFactory extends Factory
{
    /** @var class-string<\App\Models\ProductDevice> */
    protected $model = ProductDevice::class;

    public function definition(): array
    {
        /** @var \App\Enums\DeviceFormFactor $form */
        $form = Arr::random(DeviceFormFactor::cases());

        // Buat Product induk bertipe device langsung via Model (bukan ProductFactory)
        $productId = function (): int {
            $brandId = Brand::query()->inRandomOrder()->value('id') ?? Brand::factory()->create()->id;

            // Cari kategori 'devices' (atau buat kalau belum ada)
            $cat = Category::query()
                ->where('path', 'like', 'devices%')
                ->inRandomOrder()
                ->first() ?? Category::factory()->rootDevices()->create();

            $name = $this->faker->randomElement(['Foom Pod Y', 'Caliburn G4', 'VaporMax', 'Aero AIO', 'ModX']).' '.
                    $this->faker->randomElement(['Pro', 'Lite', 'SE', 'X']);

            return Product::query()->create([
                'brand_id' => $brandId,
                'primary_category_id' => $cat->id,
                'type' => ProductType::DEVICE->value,
                'name' => $name,
                'slug' => Str::slug($name).'-'.$this->faker->unique()->numberBetween(1000, 9999),
                'description' => $this->faker->optional()->paragraph(),
                'is_active' => true,
                'is_age_restricted' => true,
                'specs' => null,
            ])->id;
        };

        // Tentukan battery_type & spesifikasinya berdasarkan form factor
        [$batteryType, $batteryMah, $extFormat] = $this->batterySpecByForm($form);

        // Watt range realistis per form factor
        [$wMin, $wMax] = $this->wattRangeByForm($form);

        // Charger type (nullable untuk disposable lawas)
        $charger = $this->chargerByForm($form);

        return [
            'product_id' => $productId,
            'form_factor' => $form->value,                 // mod|pod_system|pod_refillable|disposable|aio
            'battery_type' => $batteryType?->value,         // internal|external|null
            'battery_size_mah' => $batteryMah,                  // null jika external
            'external_battery_format' => $extFormat,                   // 18650|20700|21700|null
            'watt_min' => $wMin,
            'watt_max' => $wMax,
            'charger_type' => $charger,                     // type-c|micro-usb|proprietary|null
        ];
    }

    /* =========================
     * STATE HELPERS (FORM FACTOR)
     * ========================= */

    public function mod(): static
    {
        return $this->state(fn () => ['form_factor' => DeviceFormFactor::MOD->value] + $this->stateByForm(DeviceFormFactor::MOD));
    }

    public function podSystem(): static
    {
        return $this->state(fn () => ['form_factor' => DeviceFormFactor::POD_SYSTEM->value] + $this->stateByForm(DeviceFormFactor::POD_SYSTEM));
    }

    public function podRefillable(): static
    {
        return $this->state(fn () => ['form_factor' => DeviceFormFactor::POD_REFILLABLE->value] + $this->stateByForm(DeviceFormFactor::POD_REFILLABLE));
    }

    public function disposable(): static
    {
        return $this->state(fn () => ['form_factor' => DeviceFormFactor::DISPOSABLE->value] + $this->stateByForm(DeviceFormFactor::DISPOSABLE));
    }

    public function aio(): static
    {
        return $this->state(fn () => ['form_factor' => DeviceFormFactor::AIO->value] + $this->stateByForm(DeviceFormFactor::AIO));
    }

    /* =========================
     * STATE HELPERS (BATTERY)
     * ========================= */

    public function internalBattery(?int $mah = null, ?ChargerType $charger = null): static
    {
        $mah = $mah ?? $this->faker->numberBetween(500, 3000);
        $chargerVal = $charger?->value ?? Arr::random([ChargerType::TYPE_C->value, ChargerType::MICRO_USB->value]);

        return $this->state(fn () => [
            'battery_type' => BatteryType::INTERNAL->value,
            'battery_size_mah' => $mah,
            'external_battery_format' => null,
            'charger_type' => $chargerVal,
        ]);
    }

    public function external18650(): static
    {
        return $this->externalBattery('18650');
    }

    public function external20700(): static
    {
        return $this->externalBattery('20700');
    }

    public function external21700(): static
    {
        return $this->externalBattery('21700');
    }

    public function externalBattery(string $format = '18650', ?ChargerType $charger = null): static
    {
        $chargerVal = $charger?->value ?? Arr::random([ChargerType::TYPE_C->value, ChargerType::MICRO_USB->value]);

        return $this->state(fn () => [
            'battery_type' => BatteryType::EXTERNAL->value,
            'battery_size_mah' => null,
            'external_battery_format' => $format,
            'charger_type' => $chargerVal,
        ]);
    }

    /* =========================
     * STATE HELPERS (WATT & CHARGER)
     * ========================= */

    public function wattRange(int $min, int $max): static
    {
        return $this->state(fn () => [
            'watt_min' => min($min, $max),
            'watt_max' => max($min, $max),
        ]);
    }

    public function usbC(): static
    {
        return $this->state(fn () => ['charger_type' => ChargerType::TYPE_C->value]);
    }

    public function microUsb(): static
    {
        return $this->state(fn () => ['charger_type' => ChargerType::MICRO_USB->value]);
    }

    public function proprietary(): static
    {
        return $this->state(fn () => ['charger_type' => ChargerType::PROPRIETARY->value]);
    }

    /* =========================
     * STATE HELPERS (RELASI)
     * ========================= */

    /** Kaitkan ke product device tertentu (ID atau model). Pastikan product.type = 'device'. */
    public function forProduct(Product|int $product): static
    {
        $id = $product instanceof Product ? $product->id : $product;

        return $this->state(fn () => ['product_id' => $id]);
    }

    /* =========================
     * UTIL LOGIC
     * ========================= */

    private function batterySpecByForm(DeviceFormFactor $form): array
    {
        return match ($form) {
            DeviceFormFactor::MOD => [
                BatteryType::EXTERNAL,
                null,
                Arr::random(['18650', '21700', '20700']),
            ],
            DeviceFormFactor::POD_SYSTEM, DeviceFormFactor::POD_REFILLABLE => [
                BatteryType::INTERNAL,
                $this->faker->numberBetween(400, 1500),
                null,
            ],
            DeviceFormFactor::DISPOSABLE => [
                BatteryType::INTERNAL,
                $this->faker->numberBetween(350, 800),
                null,
            ],
            DeviceFormFactor::AIO => [
                $this->faker->boolean(60) ? BatteryType::INTERNAL : BatteryType::EXTERNAL,
                $this->faker->boolean(60) ? $this->faker->numberBetween(800, 2200) : null,
                $this->faker->boolean(60) ? null : Arr::random(['18650', '21700']),
            ],
        };
    }

    private function wattRangeByForm(DeviceFormFactor $form): array
    {
        return match ($form) {
            DeviceFormFactor::MOD => [$this->faker->numberBetween(5, 15), $this->faker->numberBetween(60, 200)],
            DeviceFormFactor::POD_SYSTEM => [5, $this->faker->numberBetween(12, 25)],
            DeviceFormFactor::POD_REFILLABLE => [5, $this->faker->numberBetween(15, 40)],
            DeviceFormFactor::DISPOSABLE => [null, null], // sering tidak expose watt
            DeviceFormFactor::AIO => [5, $this->faker->numberBetween(20, 80)],
        };
    }

    private function chargerByForm(DeviceFormFactor $form): ?string
    {
        return match ($form) {
            DeviceFormFactor::DISPOSABLE => $this->faker->boolean(30) ? null : Arr::random([ChargerType::TYPE_C->value, ChargerType::MICRO_USB->value]),
            default => Arr::random([ChargerType::TYPE_C->value, ChargerType::MICRO_USB->value]),
        };
    }

    /** Bangun ulang state otomatis sesuai form factor tertentu (untuk state helper) */
    private function stateByForm(DeviceFormFactor $form): array
    {
        [$bat, $mah, $fmt] = $this->batterySpecByForm($form);
        [$wMin, $wMax] = $this->wattRangeByForm($form);
        $charger = $this->chargerByForm($form);

        return [
            'battery_type' => $bat?->value,
            'battery_size_mah' => $mah,
            'external_battery_format' => $fmt,
            'watt_min' => $wMin,
            'watt_max' => $wMax,
            'charger_type' => $charger,
        ];
    }
}
