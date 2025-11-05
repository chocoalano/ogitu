<?php

namespace Database\Factories;

use App\Enums\VendorStatus;
use App\Models\Customer;
use App\Models\Vendor;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Vendor>
 */
class VendorFactory extends Factory
{
    /** @var class-string<\App\Models\Vendor> */
    protected $model = Vendor::class;

    /**
     * Default state.
     */
    public function definition(): array
    {
        return [
            'customer_id' => Customer::factory(),
            'company_name' => rtrim($this->faker->unique()->company(), '.'),
            'npwp' => $this->faker->optional(0.6)->numerify('################'), // 16 digit
            'phone' => '08'.$this->faker->numerify('########'),               // IDN-style
            'status' => VendorStatus::ACTIVE->value,                              // pending|active|suspended
        ];
    }

    /* =========================
     * STATE HELPERS
     * ========================= */

    public function pending(): static
    {
        return $this->state(fn () => ['status' => VendorStatus::PENDING->value]);
    }

    public function active(): static
    {
        return $this->state(fn () => ['status' => VendorStatus::ACTIVE->value]);
    }

    public function suspended(): static
    {
        return $this->state(fn () => ['status' => VendorStatus::SUSPENDED->value]);
    }

    /** Kaitkan ke customer tertentu (model/ID) */
    public function forCustomer(Customer|int $customer): static
    {
        return $this->state(fn () => [
            'customer_id' => $customer instanceof Customer ? $customer->id : $customer,
        ]);
    }

    /** Set nama perusahaan spesifik (slug/unik atur di Model bila perlu) */
    public function withCompany(string $name): static
    {
        return $this->state(fn () => ['company_name' => $name]);
    }

    /** Set NPWP spesifik (atau null) */
    public function withNpwp(?string $npwp): static
    {
        return $this->state(fn () => ['npwp' => $npwp]);
    }

    /** Set nomor telepon spesifik */
    public function withPhone(string $phone): static
    {
        return $this->state(fn () => ['phone' => $phone]);
    }
}
