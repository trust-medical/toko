<?php

declare(strict_types=1);

namespace TrustMedical\Toko\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use TrustMedical\Toko\Enums\PostStatus;
use TrustMedical\Toko\Models\Post;
use TrustMedical\Toko\Models\PostStatusEvent;

final class PostStatusEventFactory extends Factory
{
    protected $model = PostStatusEvent::class;

    public function definition(): array
    {
        return [
            'post_id' => Post::factory(),
            'from_status' => PostStatus::Draft,
            'to_status' => PostStatus::Published,
            'changed_by_user_id' => 1,
            'note' => $this->faker->optional()->sentence,
            'changed_at' => now()->subMinutes($this->faker->numberBetween(1, 120)),
        ];
    }
}
