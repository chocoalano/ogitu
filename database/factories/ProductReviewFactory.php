<?php

namespace Database\Factories;

use App\Models\Customer;
use App\Models\OrderItem;
use App\Models\ProductReview;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\ProductReview>
 */
class ProductReviewFactory extends Factory
{
    /** @var class-string<\App\Models\ProductReview> */
    protected $model = ProductReview::class;

    /**
     * Default state.
     *
     * Catatan:
     * - Jika kamu sudah punya OrderItem yang valid (terkait OrderShop → Order → Customer),
     *   gunakan state helper ->forOrderItem($oi) agar customer otomatis mengikuti customer pemesanan.
     */
    public function definition(): array
    {
        // Buat OrderItem minimal (jika belum ada) — kamu bisa override via forOrderItem()
        $orderItem = OrderItem::query()->inRandomOrder()->first();

        // Tentukan customer: idealnya sama dengan customer dari Order terkait OrderItem
        $customerId = $orderItem?->orderShop?->order?->customer_id
            ?? Customer::factory()->create()->id;

        return [
            'order_item_id' => $orderItem?->id ?? OrderItem::factory(),
            'customer_id' => $customerId,
            'rating' => $this->faker->numberBetween(3, 5), // default lebih condong positif
            'comment' => $this->faker->paragraph(2),
            'created_at' => now()
                ->subDays($this->faker->numberBetween(0, 60))
                ->subMinutes($this->faker->numberBetween(0, 1440)),
        ];
    }

    /* =========================
     * STATE HELPERS
     * ========================= */

    /** Kaitkan ke OrderItem tertentu (ID atau model).
     *  Otomatis sinkron customer_id dari pesanan jika tersedia.
     */
    public function forOrderItem(OrderItem|int $orderItem): static
    {
        $oi = $orderItem instanceof OrderItem ? $orderItem : OrderItem::findOrFail($orderItem);
        $custId = $oi->orderShop?->order?->customer_id;

        return $this->state(fn () => [
            'order_item_id' => $oi->id,
            'customer_id' => $custId ?? Customer::factory(),
        ]);
    }

    /** Reviewer spesifik (ID atau model) */
    public function byCustomer(Customer|int $customer): static
    {
        return $this->state(fn () => [
            'customer_id' => $customer instanceof Customer ? $customer->id : $customer,
        ]);
    }

    /** Rating tertentu (1–5) */
    public function withRating(int $stars): static
    {
        $stars = max(1, min(5, $stars));

        return $this->state(fn () => ['rating' => $stars]);
    }

    /** Sentimen positif (4–5 bintang) */
    public function positive(): static
    {
        return $this->state(fn () => [
            'rating' => $this->faker->numberBetween(4, 5),
            'comment' => $this->faker->randomElement([
                'Kualitas mantap, rasa konsisten!',
                'Pengiriman cepat, barang original.',
                'Performa OK, baterai awet. Recommended.',
            ]),
        ]);
    }

    /** Sentimen negatif (1–2 bintang) */
    public function negative(): static
    {
        return $this->state(fn () => [
            'rating' => $this->faker->numberBetween(1, 2),
            'comment' => $this->faker->randomElement([
                'Rasa kurang sesuai deskripsi.',
                'Unit bermasalah, coil cepat gosong.',
                'Kemasan penyok, mohon ditingkatkan.',
            ]),
        ]);
    }

    /** Komentar singkat */
    public function shortText(): static
    {
        return $this->state(fn () => [
            'comment' => $this->faker->sentence(8),
        ]);
    }

    /** Komentar panjang */
    public function longText(): static
    {
        return $this->state(fn () => [
            'comment' => $this->faker->paragraph(4),
        ]);
    }

    /** Set waktu ulasan spesifik */
    public function at(\DateTimeInterface|string $when): static
    {
        return $this->state(fn () => [
            'created_at' => $when instanceof \DateTimeInterface ? $when : now()->parse($when),
        ]);
    }
}
