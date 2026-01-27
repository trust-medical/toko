<?php

declare(strict_types=1);

namespace TrustMedical\Toko\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use TrustMedical\Toko\Models\Post;
use TrustMedical\Toko\Models\PostRevision;

final class PostRevisionFactory extends Factory
{
    protected $model = PostRevision::class;

    public function definition(): array
    {
        return [
            'post_id' => Post::factory(),
            'editor_user_id' => 1,
            'title' => $this->faker->sentence(4),
            'content_json' => [
                'type' => 'doc',
                'content' => [
                    ['type' => 'paragraph', 'content' => [['type' => 'text', 'text' => $this->faker->paragraph]]],
                ],
            ],
            'content_html' => '<p>'.$this->faker->paragraph.'</p>',
            'editor' => 'tiptap',
            'editor_version' => '2.x',
            'schema_version' => 1,
            'change_note' => $this->faker->optional()->sentence,
        ];
    }
}
