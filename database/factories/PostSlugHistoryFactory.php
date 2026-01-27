<?php

declare(strict_types=1);

namespace TrustMedical\Toko\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;
use TrustMedical\Toko\Models\Post;
use TrustMedical\Toko\Models\PostSlugHistory;

final class PostSlugHistoryFactory extends Factory
{
    protected $model = PostSlugHistory::class;

    public function definition(): array
    {
        return [
            'post_id' => Post::factory(),
            'old_slug' => Str::slug($this->faker->sentence(3)).'-'.$this->faker->unique()->numberBetween(1, 9999),
            'created_at' => now()->subMinutes($this->faker->numberBetween(1, 120)),
        ];
    }
}
