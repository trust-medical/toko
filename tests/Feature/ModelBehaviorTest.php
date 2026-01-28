<?php

declare(strict_types=1);

namespace TrustMedical\Toko\Tests\Feature;

use TrustMedical\Toko\Contracts\PostEditorContract;
use TrustMedical\Toko\Contracts\PostSchedulerContract;
use TrustMedical\Toko\Enums\PostStatus;
use TrustMedical\Toko\Models\Post;
use TrustMedical\Toko\Models\PostCategory;
use TrustMedical\Toko\Models\PostRevision;
use TrustMedical\Toko\Models\PostRevisionPublish;
use TrustMedical\Toko\Models\PostRevisionSchedule;
use TrustMedical\Toko\Models\PostSlugHistory;
use TrustMedical\Toko\Models\PostStatusEvent;
use TrustMedical\Toko\Tests\Support\User;
use TrustMedical\Toko\Tests\TestCase;

final class ModelBehaviorTest extends TestCase
{
    public function test_post_scopes_and_status_cast(): void
    {
        // 投稿の状態スコープとenumキャストを一通り確認する
        $user = User::create([
            'name' => 'Editor',
            'email' => 'editor@example.com',
            'password' => 'secret',
        ]);

        $category = PostCategory::create([
            'name' => 'News',
            'slug' => 'news',
        ]);

        Post::create([
            'author_user_id' => $user->id,
            'category_id' => $category->id,
            'title' => 'Draft',
            'slug' => 'draft',
            'status' => PostStatus::Draft,
        ]);

        $published = Post::create([
            'author_user_id' => $user->id,
            'category_id' => $category->id,
            'title' => 'Published',
            'slug' => 'published',
            'status' => PostStatus::Published,
            'published_at' => now()->subHour(),
        ]);

        $this->assertSame(1, Post::draft()->count());
        $this->assertSame(1, Post::published()->count());
        $this->assertSame(1, Post::status(PostStatus::Published)->count());
        $this->assertSame(1, Post::status(PostStatus::Published->value)->count());
        $this->assertInstanceOf(PostStatus::class, $published->fresh()->status);
        $this->assertSame(2, Post::byAuthor($user->id)->count());
        $this->assertSame(2, Post::inCategory($category->id)->count());
        $this->assertSame(1, Post::publishedAt(now())->count());
        $this->assertSame(1, Post::visible(now())->count());
    }

    public function test_category_scopes(): void
    {
        // roots/ordered の並び順が意図通りになることを確認する
        $rootB = PostCategory::create([
            'name' => 'Root B',
            'slug' => 'root-b',
            'sort_order' => 1,
        ]);

        $rootA = PostCategory::create([
            'name' => 'Root A',
            'slug' => 'root-a',
            'sort_order' => 2,
        ]);

        PostCategory::create([
            'name' => 'Child',
            'slug' => 'child',
            'parent_id' => $rootA->id,
            'sort_order' => 0,
        ]);

        $roots = PostCategory::roots()->ordered()->pluck('id')->all();

        $this->assertSame([$rootB->id, $rootA->id], $roots);
    }

    public function test_tree_with_published_posts(): void
    {
        // 公開済み記事のみを含むツリーが構築されることを確認する
        $user = User::create([
            'name' => 'Author',
            'email' => 'author@example.com',
            'password' => 'secret',
        ]);

        $root = PostCategory::create([
            'name' => 'Root',
            'slug' => 'root',
            'sort_order' => 1,
        ]);

        $child = PostCategory::create([
            'name' => 'Child',
            'slug' => 'child-tree',
            'parent_id' => $root->id,
            'sort_order' => 0,
        ]);

        Post::create([
            'author_user_id' => $user->id,
            'category_id' => $root->id,
            'title' => 'Draft',
            'slug' => 'tree-draft',
            'status' => PostStatus::Draft,
        ]);

        $rootPublished = Post::create([
            'author_user_id' => $user->id,
            'category_id' => $root->id,
            'title' => 'Root Published',
            'slug' => 'root-published',
            'status' => PostStatus::Published,
            'published_at' => now()->subDay(),
        ]);

        $childPublished = Post::create([
            'author_user_id' => $user->id,
            'category_id' => $child->id,
            'title' => 'Child Published',
            'slug' => 'child-published',
            'status' => PostStatus::Published,
            'published_at' => now(),
        ]);

        Post::create([
            'author_user_id' => $user->id,
            'category_id' => $child->id,
            'title' => 'Scheduled',
            'slug' => 'child-scheduled',
            'status' => PostStatus::Scheduled,
            'scheduled_at' => now()->addDay(),
        ]);

        $tree = PostCategory::treeWithPublishedPosts();

        $this->assertCount(1, $tree);
        $rootNode = $tree->first();

        $this->assertTrue($rootNode->is($root));
        $this->assertCount(1, $rootNode->posts);
        $this->assertSame(1, $rootNode->published_posts_count);
        $this->assertTrue($rootNode->posts->first()->is($rootPublished));

        $this->assertCount(1, $rootNode->children);
        $childNode = $rootNode->children->first();

        $this->assertTrue($childNode->is($child));
        $this->assertCount(1, $childNode->posts);
        $this->assertSame(1, $childNode->published_posts_count);
        $this->assertTrue($childNode->posts->first()->is($childPublished));
    }

    public function test_latest_published_revision(): void
    {
        // latestPublishedRevision が最新の公開履歴を参照できることを確認する
        $user = User::create([
            'name' => 'Editor',
            'email' => 'editor-latest@example.com',
            'password' => 'secret',
        ]);

        $category = PostCategory::create([
            'name' => 'Latest',
            'slug' => 'latest',
        ]);

        $post = Post::create([
            'author_user_id' => $user->id,
            'category_id' => $category->id,
            'title' => 'Post',
            'slug' => 'latest-post',
            'status' => PostStatus::Draft,
        ]);

        $rev1 = PostRevision::create([
            'post_id' => $post->id,
            'editor_user_id' => $user->id,
            'title' => 'v1',
            'content_json' => ['type' => 'doc'],
            'content_html' => '<p>v1</p>',
            'editor' => 'tiptap',
            'schema_version' => 1,
        ]);

        $rev2 = PostRevision::create([
            'post_id' => $post->id,
            'editor_user_id' => $user->id,
            'title' => 'v2',
            'content_json' => ['type' => 'doc'],
            'content_html' => '<p>v2</p>',
            'editor' => 'tiptap',
            'schema_version' => 1,
        ]);

        PostRevisionPublish::create([
            'post_id' => $post->id,
            'revision_id' => $rev1->id,
            'published_by_user_id' => $user->id,
            'published_at' => now()->subDay(),
        ]);

        PostRevisionPublish::create([
            'post_id' => $post->id,
            'revision_id' => $rev2->id,
            'published_by_user_id' => $user->id,
            'published_at' => now(),
        ]);

        $latest = $post->latestPublishedRevision();

        $this->assertNotNull($latest);
        $this->assertTrue($latest->is($rev2));
    }

    public function test_revision_and_publish_relations(): void
    {
        // revision と publish の関連が正しく辿れることを確認する
        $user = User::create([
            'name' => 'Publisher',
            'email' => 'publisher@example.com',
            'password' => 'secret',
        ]);

        $category = PostCategory::create([
            'name' => 'Docs',
            'slug' => 'docs',
        ]);

        $post = Post::create([
            'author_user_id' => $user->id,
            'category_id' => $category->id,
            'title' => 'Post',
            'slug' => 'post',
            'status' => PostStatus::Draft,
        ]);

        $revision = PostRevision::create([
            'post_id' => $post->id,
            'editor_user_id' => $user->id,
            'title' => 'Post v1',
            'content_json' => ['type' => 'doc'],
            'content_html' => '<p>Hello</p>',
            'editor' => 'tiptap',
            'schema_version' => 1,
        ]);

        $publish = PostRevisionPublish::create([
            'post_id' => $post->id,
            'revision_id' => $revision->id,
            'published_by_user_id' => $user->id,
            'published_at' => now(),
        ]);

        $revision = $revision->fresh();
        $publish = $publish->fresh();

        $this->assertSame('doc', $revision->content_json['type']);
        $this->assertTrue($revision->editorUser->is($user));
        $this->assertTrue($publish->revision->is($revision));
        $this->assertTrue($publish->publishedBy->is($user));
    }

    public function test_slug_history_is_created_on_change(): void
    {
        // slug 変更時に履歴が自動作成されることを確認する
        $user = User::create([
            'name' => 'Slugger',
            'email' => 'slugger@example.com',
            'password' => 'secret',
        ]);

        $category = PostCategory::create([
            'name' => 'Slug',
            'slug' => 'slug',
        ]);

        $post = Post::create([
            'author_user_id' => $user->id,
            'category_id' => $category->id,
            'title' => 'Slug Post',
            'slug' => 'old-slug',
            'status' => PostStatus::Draft,
        ]);

        $post->update(['slug' => 'new-slug']);

        $this->assertSame(1, PostSlugHistory::count());
        $this->assertSame('old-slug', PostSlugHistory::first()->old_slug);
    }

    public function test_post_publisher_service(): void
    {
        // PostPublisher が公開処理と履歴生成を一括で行うことを確認する
        $user = User::create([
            'name' => 'Publisher',
            'email' => 'publisher-service@example.com',
            'password' => 'secret',
        ]);

        $category = PostCategory::create([
            'name' => 'Service',
            'slug' => 'service',
        ]);

        $post = Post::create([
            'author_user_id' => $user->id,
            'category_id' => $category->id,
            'title' => 'Service Post',
            'slug' => 'service-post',
            'status' => PostStatus::Draft,
        ]);

        $revision = PostRevision::create([
            'post_id' => $post->id,
            'editor_user_id' => $user->id,
            'title' => 'Service v1',
            'content_json' => ['type' => 'doc'],
            'content_html' => '<p>Service</p>',
            'editor' => 'tiptap',
            'schema_version' => 1,
        ]);

        $publisher = app(\TrustMedical\Toko\Contracts\PostPublisherContract::class);
        $publish = $publisher->publish($post, $revision, $user, now(), 'Publish via service');

        $post = $post->fresh();

        $this->assertTrue($publish->revision->is($revision));
        $this->assertSame(PostStatus::Published, $post->status);
        $this->assertSame(1, PostStatusEvent::count());
        $this->assertSame(1, PostRevisionPublish::count());
    }

    public function test_post_scheduler_service(): void
    {
        // PostScheduler が予約登録と履歴生成を一括で行うことを確認する
        $user = User::create([
            'name' => 'Scheduler',
            'email' => 'scheduler-service@example.com',
            'password' => 'secret',
        ]);

        $category = PostCategory::create([
            'name' => 'Schedule',
            'slug' => 'schedule',
        ]);

        $post = Post::create([
            'author_user_id' => $user->id,
            'category_id' => $category->id,
            'title' => 'Schedule Post',
            'slug' => 'schedule-post',
            'status' => PostStatus::Draft,
        ]);

        $revision = PostRevision::create([
            'post_id' => $post->id,
            'editor_user_id' => $user->id,
            'title' => 'Schedule v1',
            'content_json' => ['type' => 'doc'],
            'content_html' => '<p>Schedule</p>',
            'editor' => 'tiptap',
            'schema_version' => 1,
        ]);

        $scheduledAt = now()->addHour()->setMicrosecond(0);
        $scheduler = app(PostSchedulerContract::class);
        $schedule = $scheduler->schedule($post, $revision, $scheduledAt, $user, 'Schedule via service');

        $post = $post->fresh();

        $this->assertTrue($schedule->revision->is($revision));
        $this->assertSame(PostStatus::Scheduled, $post->status);
        $this->assertNull($post->published_at);
        $this->assertSame($scheduledAt->format('Y-m-d H:i:s'), $post->scheduled_at?->format('Y-m-d H:i:s'));
        $this->assertSame(1, PostRevisionSchedule::count());
        $this->assertSame(1, PostStatusEvent::count());
    }

    public function test_publish_scheduled_posts_command(): void
    {
        // 予約公開コマンドで公開されることを確認する
        $user = User::create([
            'name' => 'Scheduler',
            'email' => 'scheduler-command@example.com',
            'password' => 'secret',
        ]);

        $category = PostCategory::create([
            'name' => 'Schedule',
            'slug' => 'schedule-command',
        ]);

        $post = Post::create([
            'author_user_id' => $user->id,
            'category_id' => $category->id,
            'title' => 'Schedule Post',
            'slug' => 'schedule-command-post',
            'status' => PostStatus::Draft,
        ]);

        $revision = PostRevision::create([
            'post_id' => $post->id,
            'editor_user_id' => $user->id,
            'title' => 'Schedule v1',
            'content_json' => ['type' => 'doc'],
            'content_html' => '<p>Schedule</p>',
            'editor' => 'tiptap',
            'schema_version' => 1,
        ]);

        $scheduledAt = now()->subMinute()->setMicrosecond(0);
        $scheduler = app(PostSchedulerContract::class);
        $scheduler->schedule($post, $revision, $scheduledAt, $user, 'Schedule via command');

        $this->artisan('toko:publish-scheduled')->assertExitCode(0);

        $post = $post->fresh();

        $this->assertSame(PostStatus::Published, $post->status);
        $this->assertSame($scheduledAt->format('Y-m-d H:i:s'), $post->scheduled_at?->format('Y-m-d H:i:s'));
        $this->assertSame($scheduledAt->format('Y-m-d H:i:s'), $post->published_at?->format('Y-m-d H:i:s'));
        $this->assertSame(0, PostRevisionSchedule::count());
        $this->assertSame(1, PostRevisionPublish::count());
    }

    public function test_status_event_casts_and_relation(): void
    {
        // ステータスイベントのenumキャストとrelationを確認する
        $user = User::create([
            'name' => 'Reviewer',
            'email' => 'reviewer@example.com',
            'password' => 'secret',
        ]);

        $category = PostCategory::create([
            'name' => 'Blog',
            'slug' => 'blog',
        ]);

        $post = Post::create([
            'author_user_id' => $user->id,
            'category_id' => $category->id,
            'title' => 'Post',
            'slug' => 'post-status',
            'status' => PostStatus::Draft,
        ]);

        $event = PostStatusEvent::create([
            'post_id' => $post->id,
            'from_status' => PostStatus::Draft,
            'to_status' => PostStatus::Published,
            'changed_by_user_id' => $user->id,
            'note' => 'Publish',
            'changed_at' => now(),
        ]);

        $event = $event->fresh();

        $this->assertSame(PostStatus::Draft, $event->from_status);
        $this->assertSame(PostStatus::Published, $event->to_status);
        $this->assertTrue($event->changedBy->is($user));
    }

    public function test_post_editor_create_with_schedule(): void
    {
        // PostEditor で予約公開の作成ができることを確認する
        $user = User::create([
            'name' => 'Editor',
            'email' => 'post-editor@example.com',
            'password' => 'secret',
        ]);

        $category = PostCategory::create([
            'name' => 'Editor',
            'slug' => 'editor',
        ]);

        $scheduledAt = now()->addHour()->setMicrosecond(0);

        $editor = app(PostEditorContract::class);
        $post = $editor->create(
            [
                'author_user_id' => $user->id,
                'category_id' => $category->id,
                'title' => 'Scheduled Post',
                'slug' => 'scheduled-post',
                'status' => PostStatus::Scheduled,
                'scheduled_at' => $scheduledAt,
            ],
            [
                'title' => 'Scheduled v1',
                'content_json' => ['type' => 'doc'],
                'content_html' => '<p>Schedule</p>',
                'editor' => 'tiptap',
                'schema_version' => 1,
            ],
            $user,
            $user,
            'Schedule via editor'
        );

        $post = $post->fresh();

        $this->assertSame(PostStatus::Scheduled, $post->status);
        $this->assertNull($post->published_at);
        $this->assertSame($scheduledAt->format('Y-m-d H:i:s'), $post->scheduled_at?->format('Y-m-d H:i:s'));
        $this->assertSame(1, PostRevisionSchedule::count());
        $this->assertSame(1, PostStatusEvent::count());
    }

    public function test_post_editor_updates_status_event(): void
    {
        // PostEditor でステータス変更履歴が記録されることを確認する
        $user = User::create([
            'name' => 'Editor',
            'email' => 'post-editor-status@example.com',
            'password' => 'secret',
        ]);

        $category = PostCategory::create([
            'name' => 'Editor',
            'slug' => 'editor-status',
        ]);

        $post = Post::create([
            'author_user_id' => $user->id,
            'category_id' => $category->id,
            'title' => 'Draft',
            'slug' => 'draft-status',
            'status' => PostStatus::Draft,
        ]);

        $editor = app(PostEditorContract::class);
        $post = $editor->update(
            $post,
            ['status' => PostStatus::Archived],
            [],
            $user,
            'Archive via editor'
        );

        $this->assertSame(PostStatus::Archived, $post->status);
        $this->assertSame(1, PostStatusEvent::count());
        $event = PostStatusEvent::query()->firstOrFail();
        $this->assertSame(PostStatus::Draft, $event->from_status);
        $this->assertSame(PostStatus::Archived, $event->to_status);
    }
}
