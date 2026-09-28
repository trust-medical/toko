<?php

declare(strict_types=1);

namespace TrustMedical\Toko\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;
use TrustMedical\Toko\Enums\PostStatus;
use TrustMedical\Toko\Models\Post;
use TrustMedical\Toko\Models\PostCategory;

/**
 * @extends Factory<Post>
 */
final class PostFactory extends Factory
{
    protected $model = Post::class;

    public function definition(): array
    {
        $title = $this->faker->sentence(4);

        return [
            'author_user_id' => 1,
            'category_id' => PostCategory::factory(),
            'title' => $title,
            'excerpt' => $this->faker->optional()->paragraph(2),
            'slug' => Str::slug($title).'-'.$this->faker->unique()->numberBetween(1, 9999),
            'status' => PostStatus::Draft,
            'published_at' => null,
            'scheduled_at' => null,
        ];
    }

    public function published(): self
    {
        return $this->state(function () {
            return [
                'status' => PostStatus::Published,
                'published_at' => now()->subMinutes($this->faker->numberBetween(1, 120)),
            ];
        });
    }

    public function scheduled(): self
    {
        return $this->state(function () {
            return [
                'status' => PostStatus::Scheduled,
                'published_at' => null,
                'scheduled_at' => now()->addDays($this->faker->numberBetween(1, 7)),
            ];
        });
    }
}
