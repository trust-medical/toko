<?php

declare(strict_types=1);

namespace TrustMedical\Toko\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;
use TrustMedical\Toko\Models\PostCategory;

/**
 * @extends Factory<PostCategory>
 */
final class PostCategoryFactory extends Factory
{
    protected $model = PostCategory::class;

    public function definition(): array
    {
        $name = $this->faker->words(2, true);

        return [
            'parent_id' => null,
            'name' => $name,
            'slug' => Str::slug($name),
            'sort_order' => $this->faker->numberBetween(0, 10),
        ];
    }
}
