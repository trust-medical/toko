<?php

declare(strict_types=1);

namespace TrustMedical\Toko\Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use TrustMedical\Toko\Database\Factories\PostCategoryFactory;
use TrustMedical\Toko\Database\Factories\PostFactory;
use TrustMedical\Toko\Database\Factories\PostRevisionFactory;
use TrustMedical\Toko\Enums\PostStatus;
use TrustMedical\Toko\Models\PostRevisionPublish;
use TrustMedical\Toko\Models\PostStatusEvent;

final class TokoSeeder extends Seeder
{
    public function run(): void
    {
        $userId = DB::table('users')->value('id');

        $root = PostCategoryFactory::new()->create([
            'name' => 'News',
            'slug' => 'news',
            'sort_order' => 1,
        ]);

        $child = PostCategoryFactory::new()->create([
            'name' => 'Updates',
            'slug' => 'updates',
            'parent_id' => $root->id,
            'sort_order' => 1,
        ]);

        if ($userId === null) {
            return;
        }

        $post = PostFactory::new()->published()->create([
            'author_user_id' => $userId,
            'category_id' => $child->id,
        ]);

        $revision = PostRevisionFactory::new()->create([
            'post_id' => $post->id,
            'editor_user_id' => $userId,
            'content_html' => '<p>Welcome to Toko</p>',
        ]);

        PostRevisionPublish::create([
            'post_id' => $post->id,
            'revision_id' => $revision->id,
            'published_by_user_id' => $userId,
            'published_at' => now(),
        ]);

        PostStatusEvent::create([
            'post_id' => $post->id,
            'from_status' => PostStatus::Draft,
            'to_status' => PostStatus::Published,
            'changed_by_user_id' => $userId,
            'note' => 'Seed publish',
            'changed_at' => now(),
        ]);
    }
}
