<?php

namespace Database\Factories;

use App\Models\Category;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Category>
 */
class CategoryFactory extends Factory
{
    /** @var class-string<\App\Models\Category> */
    protected $model = Category::class;

    // Daftar child bawaan per root
    private const DEVICE_CHILDREN = ['mod', 'pod-system', 'pod-refillable', 'disposable', 'aio'];

    private const LIQUID_CHILDREN = ['freebase', 'salt'];

    private const ACCESSORY_CHILDREN = ['rda', 'rta', 'rdta', 'tank', 'cartridge', 'coil', 'cotton', 'battery', 'charger', 'tools', 'replacement-pod'];

    /**
     * Default state: root acak (aman untuk testing umum).
     */
    public function definition(): array
    {
        $name = Str::title($this->faker->unique()->word());
        $slug = Str::slug($name.'-'.$this->faker->unique()->numberBetween(10, 9999));

        return [
            'parent_id' => null,
            'name' => $name,
            'slug' => $slug,
            'path' => $slug,   // root: path = slug
            'is_age_restricted' => true,
        ];
    }

    /* =========================
     * ROOT STATES
     * ========================= */

    public function rootDevices(): static
    {
        return $this->state(fn () => [
            'parent_id' => null,
            'name' => 'Devices',
            'slug' => 'devices',
            'path' => 'devices',
            'is_age_restricted' => true,
        ]);
    }

    public function rootLiquids(): static
    {
        return $this->state(fn () => [
            'parent_id' => null,
            'name' => 'Liquids',
            'slug' => 'liquids',
            'path' => 'liquids',
            'is_age_restricted' => true,
        ]);
    }

    public function rootAccessories(): static
    {
        return $this->state(fn () => [
            'parent_id' => null,
            'name' => 'Accessories',
            'slug' => 'accessories',
            'path' => 'accessories',
            'is_age_restricted' => true,
        ]);
    }

    /* =========================
     * CHILD STATES (GENERIK)
     * ========================= */

    /**
     * Buat child untuk parent tertentu + slug tertentu.
     * Otomatis set name (Title Case) jika tidak diberikan, dan build path = parent.path/slug
     */
    public function childOf(Category|int $parent, ?string $slug = null, ?string $name = null, ?bool $restricted = true): static
    {
        return $this->state(function () use ($parent, $slug, $name, $restricted) {
            $p = $parent instanceof Category ? $parent : Category::findOrFail($parent);
            $slug = $this->ensureUniqueSlug($slug ?? Str::slug($this->faker->unique()->words(2, true)));
            $name = $name ?? Str::title(str_replace('-', ' ', $slug));

            return [
                'parent_id' => $p->id,
                'name' => $name,
                'slug' => $slug,
                'path' => rtrim($p->path, '/').'/'.$slug,
                'is_age_restricted' => $restricted ?? true,
            ];
        });
    }

    /* =========================
     * CHILD STATES (SPESIFIK)
     * ========================= */

    public function deviceChild(Category|int $devicesRoot): static
    {
        return $this->state(function () use ($devicesRoot) {
            $slug = Arr::random(self::DEVICE_CHILDREN);
            $p = $devicesRoot instanceof Category ? $devicesRoot : Category::findOrFail($devicesRoot);
            $slug = $this->ensureUniqueSlug($slug);

            return [
                'parent_id' => $p->id,
                'name' => Str::title(str_replace('-', ' ', $slug)), // Mod, Pod System, ...
                'slug' => $slug,
                'path' => $p->path.'/'.$slug,
                'is_age_restricted' => true,
            ];
        });
    }

    public function liquidChild(Category|int $liquidsRoot): static
    {
        return $this->state(function () use ($liquidsRoot) {
            $slug = Arr::random(self::LIQUID_CHILDREN);
            $p = $liquidsRoot instanceof Category ? $liquidsRoot : Category::findOrFail($liquidsRoot);
            $slug = $this->ensureUniqueSlug($slug);

            return [
                'parent_id' => $p->id,
                'name' => Str::title($slug), // Freebase, Salt
                'slug' => $slug,
                'path' => $p->path.'/'.$slug,
                'is_age_restricted' => true,
            ];
        });
    }

    public function accessoryChild(Category|int $accessoriesRoot): static
    {
        return $this->state(function () use ($accessoriesRoot) {
            $slug = Arr::random(self::ACCESSORY_CHILDREN);
            $p = $accessoriesRoot instanceof Category ? $accessoriesRoot : Category::findOrFail($accessoriesRoot);
            $slug = $this->ensureUniqueSlug($slug);

            return [
                'parent_id' => $p->id,
                'name' => Str::title(str_replace('-', ' ', $slug)), // Replacement Pod -> Replacement Pod
                'slug' => $slug,
                'path' => $p->path.'/'.$slug,
                'is_age_restricted' => true,
            ];
        });
    }

    /* =========================
     * UTIL
     * ========================= */

    /**
     * Pastikan slug unik terhadap constraint unique(slug).
     * (Jika slug sudah ada, tambahkan suffix -2, -3, dst.)
     */
    private function ensureUniqueSlug(string $slug): string
    {
        $base = $slug;
        $i = 2;
        while (Category::where('slug', $slug)->exists()) {
            $slug = $base.'-'.$i;
            $i++;
        }

        return $slug;
    }
}
