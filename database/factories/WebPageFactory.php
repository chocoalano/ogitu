<?php

namespace Database\Factories;

use App\Models\WebPage;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class WebPageFactory extends Factory
{
    protected $model = WebPage::class;

    public function definition(): array
    {
        // Ambil slug valid dari konstanta model (harus sesuai enum di migration)
        $slug = $this->faker->unique()->randomElement(WebPage::SCHEMA_KEYS);
        $name = Str::headline($slug);

        // === Builder Blocks Content ===
        // 1) Heading
        $headingBlock = [
            'type' => 'heading',
            'data' => [
                // level h2/h3 (default h2 biar konsisten SEO)
                'level'   => $this->faker->boolean(75) ? 'h2' : 'h3',
                'content' => Str::headline($this->faker->sentence(mt_rand(5, 9))),
            ],
        ];

        // 2) Paragraph HTML <p>..</p> dengan <br><br> antar sub-paragraf
        $subParagraphs = $this->faker->paragraphs(mt_rand(3, 5));
        $html = '<p>' . implode('<br><br>', array_map(function ($p) {
            // Sedikit rapihin kalimat agar tidak terlalu panjang
            return e($p);
        }, $subParagraphs)) . '</p>';

        $paragraphBlock = [
            'type' => 'paragraph',
            'data' => [
                'content' => $html,
            ],
        ];

        $blocks = [$headingBlock, $paragraphBlock];

        // Meta
        $seoTitle   = Str::limit($name, 60, '');
        $metaDesc   = Str::limit($this->faker->sentences(3, true), 160, '');
        $metaKeys   = $this->faker->optional()->words(mt_rand(3, 6));

        return [
            'name'              => $name,
            'slug'              => $slug,
            'path'              => '/'.$slug,
            'route_name'        => null,
            'position'          => $this->faker->numberBetween(1, 1000),

            // enum layout di migration: topbar|navbar|footer
            'layout'            => $this->faker->randomElement(WebPage::LAYOUTS),

            // schema_type = slug (sesuai enum di migration)
            'schema_type'       => $slug,

            // === Kolom content: array blok sesuai permintaan ===
            'content'           => $blocks,

            // SEO
            'seo_title'         => $seoTitle,
            'meta_description'  => $metaDesc,
            'meta_keywords'     => $metaKeys,

            // Flags
            'noindex'           => false,
            'nofollow'          => false,
            'is_active'         => true,

            'excerpt'           => $this->faker->optional(0.4)->paragraph(),
        ];
    }

    /** State: selalu di navbar */
    public function navbar(): static
    {
        return $this->state(fn () => ['layout' => 'navbar']);
    }

    /** State: selalu di footer */
    public function footer(): static
    {
        return $this->state(fn () => ['layout' => 'footer']);
    }

    /** State: selalu di topbar */
    public function topbar(): static
    {
        return $this->state(fn () => ['layout' => 'topbar']);
    }

    /** State: non-aktif */
    public function inactive(): static
    {
        return $this->state(fn () => ['is_active' => false]);
    }

    /**
     * State: pakai slug tertentu (harus ada dalam WebPage::SCHEMA_KEYS).
     */
    public function pageSlug(string $slug): static
    {
        return $this->state(function () use ($slug) {
            if (!in_array($slug, WebPage::SCHEMA_KEYS, true)) {
                throw new \InvalidArgumentException("Slug '{$slug}' tidak valid untuk schema_type.");
            }

            $name = Str::headline($slug);

            // Buat blok konten sesuai slug
            $headingBlock = [
                'type' => 'heading',
                'data' => [
                    'level'   => 'h2',
                    'content' => Str::headline($this->faker->sentence(mt_rand(5, 9))),
                ],
            ];

            $subParagraphs = $this->faker->paragraphs(mt_rand(3, 5));
            $html = '<p>' . implode('<br><br>', array_map(fn ($p) => e($p), $subParagraphs)) . '</p>';

            $paragraphBlock = [
                'type' => 'paragraph',
                'data' => [
                    'content' => $html,
                ],
            ];

            return [
                'name'             => $name,
                'slug'             => $slug,
                'path'             => '/'.$slug,
                'schema_type'      => $slug,
                'seo_title'        => Str::limit($name, 60, ''),
                'content'          => [$headingBlock, $paragraphBlock],
            ];
        });
    }
}
