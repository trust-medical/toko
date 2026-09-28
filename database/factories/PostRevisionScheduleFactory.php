<?php

declare(strict_types=1);

namespace TrustMedical\Toko\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use TrustMedical\Toko\Models\Post;
use TrustMedical\Toko\Models\PostRevision;
use TrustMedical\Toko\Models\PostRevisionSchedule;

/**
 * @extends Factory<PostRevisionSchedule>
 */
final class PostRevisionScheduleFactory extends Factory
{
    protected $model = PostRevisionSchedule::class;

    public function definition(): array
    {
        return [
            'post_id' => Post::factory(),
            'revision_id' => PostRevision::factory()->state(function (array $attributes): array {
                return [
                    'post_id' => $attributes['post_id'] ?? null,
                ];
            }),
            'scheduled_by_user_id' => 1,
        ];
    }
}
