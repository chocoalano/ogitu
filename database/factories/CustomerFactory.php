<?php

namespace Database\Factories;

use App\Enums\CustomerStatus;
use App\Models\Customer;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Customer>
 */
class CustomerFactory extends Factory
{
    /** @var class-string<\App\Models\Customer> */
    protected $model = Customer::class;

    /**
     * Default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => $this->faker->name(),
            'email' => $this->faker->unique()->safeEmail(),
            'phone' => '08'.$this->faker->numerify('##########'),
            'dob' => $this->faker->dateTimeBetween('-55 years', '-19 years'), // >=19 thn
            'password' => bcrypt('password'), // ganti saat seeding produksi
            'status' => CustomerStatus::ACTIVE->value, // 'active'
        ];
    }

    /* =========================
     * State helpers
     * ========================= */

    public function active(): static
    {
        return $this->state(fn () => [
            'status' => CustomerStatus::ACTIVE->value,
        ]);
    }

    public function suspended(): static
    {
        return $this->state(fn () => [
            'status' => CustomerStatus::SUSPENDED->value,
        ]);
    }

    /** Pelanggan dewasa (atur rentang usia, default 21–55) */
    public function adult(int $minAge = 21, int $maxAge = 55): static
    {
        return $this->state(fn () => [
            'dob' => $this->faker->dateTimeBetween("-{$maxAge} years", "-{$minAge} years"),
        ]);
    }

    /** Pelanggan di bawah umur (untuk pengujian age-gate) */
    public function underage(int $minAge = 13, int $maxAge = 17): static
    {
        return $this->state(fn () => [
            'dob' => $this->faker->dateTimeBetween("-{$maxAge} years", "-{$minAge} years"),
        ]);
    }

    /** Set password khusus (di-hash) */
    public function withPassword(string $plain): static
    {
        return $this->state(fn () => [
            'password_hash' => bcrypt($plain),
        ]);
    }

    /** Set email tertentu (berguna saat butuh login test spesifik) */
    public function withEmail(string $email): static
    {
        return $this->state(fn () => ['email' => $email]);
    }

    /** Set nomor telepon tertentu (atau null) */
    public function withPhone(?string $phone): static
    {
        return $this->state(fn () => ['phone' => $phone]);
    }
}
