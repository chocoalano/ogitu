<?php

namespace Database\Factories;

use App\Models\Address;
use App\Models\Customer;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Address>
 */
class AddressFactory extends Factory
{
    /** @var class-string<\App\Models\Address> */
    protected $model = Address::class;

    /**
     * Default state.
     */
    public function definition(): array
    {
        return [
            'customer_id' => Customer::factory(),
            'label' => $this->faker->randomElement(['Rumah', 'Kantor', 'Gudang']),
            'recipient_name' => $this->faker->name(),
            'phone' => '08'.$this->faker->numerify('##########'),
            'line1' => $this->faker->streetAddress(),
            'line2' => $this->faker->optional()->streetAddress(),
            'city' => $this->faker->city(),
            'state' => $this->faker->state(),
            'postal_code' => $this->faker->postcode(),
            'country_code' => 'ID',
            'is_default_shipping' => false,
            'is_default_billing' => false,
        ];
    }

    /**
     * State: alamat default untuk pengiriman.
     */
    public function defaultShipping(): static
    {
        return $this->state(fn () => [
            'is_default_shipping' => true,
        ]);
    }

    /**
     * State: alamat default untuk penagihan.
     */
    public function defaultBilling(): static
    {
        return $this->state(fn () => [
            'is_default_billing' => true,
        ]);
    }

    /**
     * Helper: set customer tertentu (bisa ID atau model).
     */
    public function forCustomer(Customer|int $customer): static
    {
        return $this->state(fn () => [
            'customer_id' => $customer instanceof Customer ? $customer->id : $customer,
        ]);
    }
}
