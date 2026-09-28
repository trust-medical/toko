<?php

declare(strict_types=1);

namespace TrustMedical\Toko\Tests\Feature;

use Illuminate\Database\QueryException;
use InvalidArgumentException;
use TrustMedical\Toko\Contracts\PostEditorContract;
use TrustMedical\Toko\Contracts\PostPublisherContract;
use TrustMedical\Toko\Contracts\PostRevisionRestorerContract;
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

final class ServiceRobustnessTest extends TestCase
{
    private User $user;

    private PostCategory $category;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::create([
            'name' => 'User',
            'email' => 'user@example.com',
            'password' => 'secret',
        ]);

        $this->category = PostCategory::create([
            'name' => 'Robust',
            'slug' => 'robust',
        ]);
    }

    public function test_publish_without_publisher_is_allowed(): void
    {
        $post = $this->makePost('system');
        $revision = $this->makeRevision($post);

        $publish = app(PostPublisherContract::class)->publish($post, $revision);

        $this->assertNull($publish->fresh()?->published_by_user_id);
        $this->assertSame(PostStatus::Published, $post->fresh()?->status);
    }

    public function test_republishing_same_revision_is_rejected_and_restore_allows_it(): void
    {
        $post = $this->makePost('republish');
        $revision = $this->makeRevision($post);
        $publisher = app(PostPublisherContract::class);

        $publisher->publish($post, $revision, $this->user);
        app(PostEditorContract::class)->update($post, ['status' => PostStatus::Archived], [], $this->user);

        try {
            $publisher->publish($post->fresh(), $revision, $this->user);
            $this->fail('Expected InvalidArgumentException.');
        } catch (InvalidArgumentException $e) {
            $this->assertStringContainsString('already been published', $e->getMessage());
        }

        $restored = app(PostRevisionRestorerContract::class)->restore($post->fresh(), $revision, $this->user);
        $publisher->publish($post->fresh(), $restored, $this->user);

        $this->assertSame(2, PostRevisionPublish::count());
        $this->assertSame(PostStatus::Published, $post->fresh()?->status);
    }

    public function test_publishing_new_revision_of_published_post_does_not_record_status_event(): void
    {
        $post = $this->makePost('republish-new');
        $publisher = app(PostPublisherContract::class);

        $publisher->publish($post, $this->makeRevision($post), $this->user);
        $publisher->publish($post->fresh(), $this->makeRevision($post, ['title' => 'v2']), $this->user);

        $this->assertSame(1, PostStatusEvent::count());
        $this->assertSame(2, PostRevisionPublish::count());
        $this->assertSame('v2', $post->fresh()?->title);
    }

    public function test_scheduling_published_post_is_rejected(): void
    {
        $post = $this->makePost('published-schedule');
        app(PostPublisherContract::class)->publish($post, $this->makeRevision($post), $this->user);

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Cannot schedule a published post.');

        app(PostSchedulerContract::class)->schedule($post->fresh(), $this->makeRevision($post), now()->addDay(), $this->user);
    }

    public function test_rescheduling_does_not_record_status_event(): void
    {
        $post = $this->makePost('reschedule');
        $revision = $this->makeRevision($post);
        $scheduler = app(PostSchedulerContract::class);

        $scheduler->schedule($post, $revision, now()->addDay(), $this->user);
        $scheduler->schedule($post->fresh(), $revision, now()->addDays(2), $this->user);

        $this->assertSame(1, PostStatusEvent::count());
        $this->assertSame(1, PostRevisionSchedule::count());
    }

    public function test_restorer_keeps_post_slug_when_revision_slug_is_null(): void
    {
        $post = $this->makePost('keep-slug');
        $revision = $this->makeRevision($post, ['slug' => null]);

        app(PostRevisionRestorerContract::class)->restore($post, $revision, $this->user);

        $this->assertSame('keep-slug', $post->fresh()?->slug);
    }

    public function test_editor_create_always_starts_from_draft(): void
    {
        config()->set('toko.default_status', PostStatus::Archived);
        $editor = app(PostEditorContract::class);

        $draft = $editor->create($this->postAttributes('explicit-draft', ['status' => PostStatus::Draft]), $this->revisionAttributes(), $this->user);
        $this->assertSame(PostStatus::Draft, $draft->status);
        $this->assertSame(0, PostStatusEvent::count());

        $archived = $editor->create($this->postAttributes('default-archived'), $this->revisionAttributes(), $this->user);
        $this->assertSame(PostStatus::Archived, $archived->status);
        $event = PostStatusEvent::query()->firstOrFail();
        $this->assertSame(PostStatus::Draft, $event->from_status);
        $this->assertSame(PostStatus::Archived, $event->to_status);
    }

    public function test_editor_create_requires_category(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('category_id is required');

        app(PostEditorContract::class)->create(
            ['title' => 'No category', 'slug' => 'no-category'],
            $this->revisionAttributes(),
            $this->user
        );
    }

    public function test_editor_publishes_latest_revision_when_revision_is_omitted(): void
    {
        $editor = app(PostEditorContract::class);
        $post = $editor->create($this->postAttributes('latest'), $this->revisionAttributes(), $this->user);
        $latest = $this->makeRevision($post, ['title' => 'Latest']);

        $post = $editor->update($post, ['status' => PostStatus::Published], [], $this->user);

        $this->assertSame(PostStatus::Published, $post->status);
        $this->assertTrue($post->latestPublishedRevision()?->is($latest));
        $this->assertSame('Latest', $post->title);
    }

    public function test_editor_reschedule_goes_through_scheduler(): void
    {
        $editor = app(PostEditorContract::class);
        $post = $editor->create(
            $this->postAttributes('editor-reschedule', ['status' => PostStatus::Scheduled, 'scheduled_at' => now()->addDay()]),
            $this->revisionAttributes(),
            $this->user
        );

        $newScheduledAt = now()->addDays(3)->setMicrosecond(0);
        $post = $editor->update($post, ['scheduled_at' => $newScheduledAt], [], $this->user);

        $this->assertSame($newScheduledAt->format('Y-m-d H:i:s'), $post->scheduled_at?->format('Y-m-d H:i:s'));
        $this->assertSame(1, PostStatusEvent::count());

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('content_html is required when scheduling.');

        $editor->update($post, [], $this->revisionAttributes(['content_html' => null]), $this->user);
    }

    public function test_editor_ignores_scheduled_at_for_draft(): void
    {
        $editor = app(PostEditorContract::class);
        $post = $editor->create($this->postAttributes('draft-scheduled-at'), $this->revisionAttributes(), $this->user);

        $post = $editor->update($post, ['scheduled_at' => now()->addDay()], [], $this->user);

        $this->assertNull($post->scheduled_at);
    }

    public function test_command_continues_after_failure_and_publishes_without_scheduler_user(): void
    {
        $scheduler = app(PostSchedulerContract::class);

        $failing = $this->makePost('failing');
        $failingRevision = $this->makeRevision($failing);
        $scheduler->schedule($failing, $failingRevision, now()->addMinute(), $this->user);
        // 予約後にHTMLが消えた状態を作り、公開時の検証で失敗させる
        PostRevision::query()->whereKey($failingRevision->id)->update(['content_html' => null]);

        $orphanUser = User::create(['name' => 'Gone', 'email' => 'gone@example.com', 'password' => 'secret']);
        $ok = $this->makePost('ok');
        $scheduler->schedule($ok, $this->makeRevision($ok), now()->addMinutes(2), $orphanUser);
        $orphanUser->delete();

        $this->travel(10)->minutes();

        $this->artisan('toko:publish-scheduled')->assertExitCode(1);

        $this->assertSame(PostStatus::Scheduled, $failing->fresh()?->status);
        $this->assertSame(PostStatus::Published, $ok->fresh()?->status);
        $this->assertNull(PostRevisionPublish::query()->where('post_id', $ok->id)->value('published_by_user_id'));
    }

    public function test_slug_history_is_not_written_when_update_fails(): void
    {
        $this->makePost('taken');
        $post = $this->makePost('mine');

        try {
            $post->update(['slug' => 'taken']);
            $this->fail('Expected QueryException.');
        } catch (QueryException) {
            // 一意制約違反を想定
        }

        $this->assertSame(0, PostSlugHistory::count());
    }

    public function test_slug_history_is_removed_when_slug_is_reused(): void
    {
        $post = $this->makePost('first');
        $post->update(['slug' => 'second']);
        $post->update(['slug' => 'first']);

        $this->assertSame(['second'], PostSlugHistory::query()->pluck('old_slug')->all());

        $other = $this->makePost('other');
        $post->update(['slug' => 'third']);
        $other->update(['slug' => 'third-other']);
        $post->update(['slug' => 'second']);
        $other->update(['slug' => 'third']);
        $other->update(['slug' => 'fourth']);

        $this->assertSame($other->id, PostSlugHistory::query()->where('old_slug', 'third')->value('post_id'));
    }

    public function test_scheduled_factory_uses_scheduled_at(): void
    {
        $post = Post::factory()->scheduled()->create(['author_user_id' => $this->user->id]);

        $this->assertSame(PostStatus::Scheduled, $post->status);
        $this->assertNull($post->published_at);
        $this->assertNotNull($post->scheduled_at);
    }

    public function test_readme_quick_start_flow(): void
    {
        // README のクイックスタートの流れが通ることを確認する
        $editor = app(PostEditorContract::class);
        $publisher = app(PostPublisherContract::class);
        $content = ['content_json' => ['type' => 'doc'], 'content_html' => '<p>Body</p>'];

        $post = $editor->create(
            ['category_id' => $this->category->id, 'slug' => 'hello-toko'],
            ['title' => 'Hello Toko'] + $content,
            author: $this->user,
        );
        $post = $editor->update($post, [], ['title' => 'Hello Toko (v2)'] + $content, $this->user);
        $post = $editor->update($post, ['status' => PostStatus::Published], [], $this->user, 'First release');
        $this->assertSame('Hello Toko (v2)', $post->title);

        $post = $editor->update($post, [], ['title' => 'Hello, Toko'] + $content, $this->user);
        $this->assertSame('Hello Toko (v2)', $post->title);
        $publisher->publish($post, $post->revisions()->latest('id')->firstOrFail(), $this->user);
        $this->assertSame('Hello, Toko', $post->fresh()?->title);

        $first = $post->revisions()->oldest('id')->firstOrFail();
        $restored = app(PostRevisionRestorerContract::class)->restore($post, $first, $this->user);
        $publisher->publish($post->fresh(), $restored, $this->user, note: 'Rollback');

        $this->assertSame('Hello Toko', $post->fresh()->title);
        $this->assertSame(3, PostRevisionPublish::count());
        $this->assertSame(1, PostStatusEvent::count());
    }

    private function makePost(string $slug): Post
    {
        return Post::create([
            'author_user_id' => $this->user->id,
            'category_id' => $this->category->id,
            'title' => 'Post '.$slug,
            'slug' => $slug,
            'status' => PostStatus::Draft,
        ]);
    }

    /**
     * @param  array<string, mixed>  $attributes
     */
    private function makeRevision(Post $post, array $attributes = []): PostRevision
    {
        return PostRevision::create(array_merge([
            'post_id' => $post->id,
            'editor_user_id' => $this->user->id,
            'title' => 'v1',
            'content_json' => ['type' => 'doc'],
            'content_html' => '<p>v1</p>',
        ], $attributes));
    }

    /**
     * @param  array<string, mixed>  $overrides
     * @return array<string, mixed>
     */
    private function postAttributes(string $slug, array $overrides = []): array
    {
        return array_merge([
            'author_user_id' => $this->user->id,
            'category_id' => $this->category->id,
            'title' => 'Post '.$slug,
            'slug' => $slug,
        ], $overrides);
    }

    /**
     * @param  array<string, mixed>  $overrides
     * @return array<string, mixed>
     */
    private function revisionAttributes(array $overrides = []): array
    {
        return array_merge([
            'title' => 'Revision',
            'content_json' => ['type' => 'doc'],
            'content_html' => '<p>Revision</p>',
        ], $overrides);
    }
}
