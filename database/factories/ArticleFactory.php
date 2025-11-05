<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Article>
 */
class ArticleFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $title = fake()->sentence(6);

        return [
            'title' => $title,
            'slug' => fake()->unique()->slug(3),
            'excerpt' => fake()->paragraph(2),
            'content' => [
                [
                    'type' => 'heading',
                    'data' => ['level' => 'h2', 'content' => fake()->sentence()],
                ],
                [
                    'type' => 'paragraph',
                    'data' => ['content' => '<p>'.fake()->paragraphs(3, true).'</p>'],
                ],
            ],
            'cover_url' => null,
            'cover_alt' => null,
            'category_id' => \App\Models\ArticleCategory::factory(),
            'tags' => [],
            'seo_title' => $title,
            'canonical_url' => null,
            'meta_description' => fake()->sentence(),
            'meta_keywords' => [],
            'noindex' => false,
            'nofollow' => false,
            'status' => 'published',
            'published_at' => now(),
            'author_id' => \App\Models\User::factory(),
        ];
    }

    /**
     * Indicate that the article is a draft.
     */
    public function draft(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'draft',
            'published_at' => null,
        ]);
    }

    /**
     * Indicate that the article is scheduled.
     */
    public function scheduled(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'scheduled',
            'published_at' => now()->addWeek(),
        ]);
    }
}
