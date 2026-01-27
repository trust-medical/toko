<?php

declare(strict_types=1);

namespace TrustMedical\Toko\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use TrustMedical\Toko\Models\Post;
use TrustMedical\Toko\Models\PostRevision;
use TrustMedical\Toko\Models\PostRevisionPublish;

final class PostRevisionPublishFactory extends Factory
{
    protected $model = PostRevisionPublish::class;

    public function definition(): array
    {
        return [
            'post_id' => Post::factory(),
            'revision_id' => PostRevision::factory()->state(function (array $attributes): array {
                return [
                    'post_id' => $attributes['post_id'] ?? null,
                ];
            }),
            'published_by_user_id' => 1,
            'published_at' => now()->subMinutes($this->faker->numberBetween(1, 120)),
        ];
    }
}
