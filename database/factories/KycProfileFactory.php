<?php

namespace Database\Factories;

use App\Models\Customer;
use App\Models\KycProfile;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Arr;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\KycProfile>
 */
class KycProfileFactory extends Factory
{
    /** @var class-string<\App\Models\KycProfile> */
    protected $model = KycProfile::class;

    /**
     * Default state.
     * - id_type acak: ktp | passport | other
     * - status default: pending (verified_at = null)
     */
    public function definition(): array
    {
        $idType = Arr::random(['ktp', 'passport', 'other']);

        return [
            'customer_id' => Customer::factory(),
            'id_type' => $idType,
            'id_number' => $this->fakeIdNumber($idType),
            'full_name_on_id' => $this->faker->name(),
            'status' => 'pending',   // pending | verified | rejected
            'verified_at' => null,
            'notes' => null,
        ];
    }

    /**
     * Setelah dibuat: jika kamu ingin sinkron nama dengan customer,
     * cukup gunakan state helper ->nameFromCustomer() saat seeding.
     */
    public function configure(): static
    {
        return $this;
    }

    /* =========================
     * STATE HELPERS (STATUS)
     * ========================= */

    /** Status: pending */
    public function pending(): static
    {
        return $this->state(fn () => [
            'status' => 'pending',
            'verified_at' => null,
            'notes' => null,
        ]);
    }

    /** Status: verified (+ set verified_at) */
    public function verified(?Carbon $when = null): static
    {
        return $this->state(fn () => [
            'status' => 'verified',
            'verified_at' => ($when ?? now()->subDays(rand(0, 30))),
            'notes' => null,
        ]);
    }

    /** Status: rejected (opsional alasan) */
    public function rejected(?string $reason = null): static
    {
        return $this->state(fn () => [
            'status' => 'rejected',
            'verified_at' => null,
            'notes' => $reason ?: $this->faker->randomElement([
                'Dokumen blur/tidak terbaca',
                'Nama tidak sesuai identitas',
                'Nomor identitas tidak valid',
            ]),
        ]);
    }

    /* =========================
     * STATE HELPERS (ID TYPE)
     * ========================= */

    /** ID: KTP (NIK 16 digit) */
    public function idKtp(?string $nik = null): static
    {
        return $this->state(fn () => [
            'id_type' => 'ktp',
            'id_number' => $nik ?: $this->fakeIdNumber('ktp'),
        ]);
    }

    /** ID: Passport (format sederhana: 1 huruf + 7 digit) */
    public function idPassport(?string $no = null): static
    {
        return $this->state(fn () => [
            'id_type' => 'passport',
            'id_number' => $no ?: $this->fakeIdNumber('passport'),
        ]);
    }

    /** ID: Other (generic) */
    public function idOther(?string $no = null): static
    {
        return $this->state(fn () => [
            'id_type' => 'other',
            'id_number' => $no ?: $this->fakeIdNumber('other'),
        ]);
    }

    /* =========================
     * STATE HELPERS (MISC)
     * ========================= */

    /** Samakan nama pada KYC dengan nama customer terkait */
    public function nameFromCustomer(Customer|int $customer): static
    {
        $cid = $customer instanceof Customer ? $customer->id : $customer;
        $name = $customer instanceof Customer ? $customer->name : (Customer::find($cid)?->name ?? null);

        return $this->state(fn () => [
            'customer_id' => $cid,
            'full_name_on_id' => $name ?: $this->faker->name(),
        ]);
    }

    /** Set customer tertentu (ID atau model) */
    public function forCustomer(Customer|int $customer): static
    {
        return $this->state(fn () => [
            'customer_id' => $customer instanceof Customer ? $customer->id : $customer,
        ]);
    }

    /** Set waktu verifikasi spesifik */
    public function verifiedAt(Carbon|string $when): static
    {
        return $this->state(fn () => [
            'verified_at' => $when instanceof Carbon ? $when : Carbon::parse($when),
        ]);
    }

    /** Set catatan */
    public function withNotes(string $notes): static
    {
        return $this->state(fn () => ['notes' => $notes]);
    }

    /* =========================
     * UTIL
     * ========================= */

    private function fakeIdNumber(string $type): string
    {
        return match ($type) {
            'ktp' => $this->faker->numerify('################'),            // 16 digit
            'passport' => strtoupper($this->faker->bothify('?#######')),    // 1 huruf + 7 digit
            default => strtoupper($this->faker->bothify('ID##########')),   // generic
        };
    }
}
