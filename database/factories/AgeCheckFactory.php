<?php

namespace Database\Factories;

use App\Models\AgeCheck;
use App\Models\Customer;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Arr;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\AgeCheck>
 */
class AgeCheckFactory extends Factory
{
    /** @var class-string<\App\Models\AgeCheck> */
    protected $model = AgeCheck::class;

    /**
     * Default state.
     */
    public function definition(): array
    {
        $method = Arr::random(['kyc', 'selfie_liveness', 'manual']);

        // Probabilitas lulus lebih tinggi untuk KYC/selfie
        $result = match ($method) {
            'kyc', 'selfie_liveness' => $this->faker->boolean(85) ? 'pass' : 'fail',
            default => $this->faker->boolean(65) ? 'pass' : 'fail',
        };

        return [
            'customer_id' => Customer::factory(),
            'method' => $method,     // 'kyc' | 'selfie_liveness' | 'manual'
            'result' => $result,     // 'pass' | 'fail'
            'checked_at' => Carbon::now()
                ->subDays($this->faker->numberBetween(0, 180))
                ->subMinutes($this->faker->numberBetween(0, 1440)),
        ];
    }

    /** State: hasil lulus */
    public function passed(): static
    {
        return $this->state(fn () => ['result' => 'pass']);
    }

    /** State: hasil gagal */
    public function failed(): static
    {
        return $this->state(fn () => ['result' => 'fail']);
    }

    /** State: metode KYC */
    public function kyc(): static
    {
        return $this->state(fn () => ['method' => 'kyc']);
    }

    /** State: metode selfie+liveness */
    public function selfie(): static
    {
        return $this->state(fn () => ['method' => 'selfie_liveness']);
    }

    /** State: metode manual */
    public function manual(): static
    {
        return $this->state(fn () => ['method' => 'manual']);
    }

    /** Helper: set ke customer tertentu */
    public function forCustomer(Customer|int $customer): static
    {
        return $this->state(fn () => [
            'customer_id' => $customer instanceof Customer ? $customer->id : $customer,
        ]);
    }
}
