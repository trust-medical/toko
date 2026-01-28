<?php

declare(strict_types=1);

namespace TrustMedical\Toko\Services;

use DateTimeInterface;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Arr;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;
use TrustMedical\Toko\Contracts\PostEditorContract;
use TrustMedical\Toko\Contracts\PostPublisherContract;
use TrustMedical\Toko\Contracts\PostSchedulerContract;
use TrustMedical\Toko\Enums\PostStatus;
use TrustMedical\Toko\Events\PostStatusChanged;
use TrustMedical\Toko\Models\Post;
use TrustMedical\Toko\Models\PostRevision;
use TrustMedical\Toko\Models\PostRevisionSchedule;
use TrustMedical\Toko\Models\PostStatusEvent;

final class PostEditor implements PostEditorContract
{
    public function __construct(
        private readonly PostPublisherContract $publisher,
        private readonly PostSchedulerContract $scheduler
    ) {}

    /**
     * @param  array<string, mixed>  $postAttributes
     * @param  array<string, mixed>  $revisionAttributes
     */
    public function create(
        array $postAttributes,
        array $revisionAttributes,
        ?Model $author = null,
        ?Model $editor = null,
        ?string $note = null
    ): Post {
        $defaultStatus = $this->defaultStatus();
        $targetStatus = $this->resolveStatus($postAttributes['status'] ?? $defaultStatus);
        $initialStatus = in_array($targetStatus, [PostStatus::Published, PostStatus::Scheduled], true)
            ? PostStatus::Draft
            : $defaultStatus;

        $postData = Arr::except($postAttributes, ['status', 'published_at', 'scheduled_at']);
        if ($author !== null && ! array_key_exists('author_user_id', $postData)) {
            $postData['author_user_id'] = $author->getKey();
        }

        $postData['status'] = $initialStatus;
        $postData['published_at'] = null;
        $postData['scheduled_at'] = null;

        return DB::transaction(function () use ($postData, $revisionAttributes, $editor, $author, $note, $targetStatus, $initialStatus, $postAttributes): Post {
            $post = Post::create($postData);
            $revision = $this->createRevision($post, $revisionAttributes, $editor ?? $author);

            if ($targetStatus !== $initialStatus) {
                $this->applyStatusChange($post, $revision, $targetStatus, $editor ?? $author, $note, $postAttributes);
            }

            return $post->fresh();
        });
    }

    /**
     * @param  array<string, mixed>  $postAttributes
     * @param  array<string, mixed>  $revisionAttributes
     */
    public function update(
        Post $post,
        array $postAttributes = [],
        array $revisionAttributes = [],
        ?Model $changedBy = null,
        ?string $note = null
    ): Post {
        $fromStatus = $this->resolveStatus($post->status);
        $targetStatus = array_key_exists('status', $postAttributes)
            ? $this->resolveStatus($postAttributes['status'])
            : $fromStatus;

        $postData = Arr::except($postAttributes, ['status', 'published_at', 'scheduled_at']);

        return DB::transaction(function () use ($post, $postData, $revisionAttributes, $changedBy, $note, $fromStatus, $targetStatus, $postAttributes): Post {
            if ($postData !== []) {
                $post->fill($postData)->save();
            }

            $revision = null;
            if ($revisionAttributes !== []) {
                $revision = $this->createRevision($post, $revisionAttributes, $changedBy);
            }

            if ($targetStatus !== $fromStatus) {
                $this->applyStatusChange($post, $revision, $targetStatus, $changedBy, $note, $postAttributes);
            } else {
                $this->applyDateAttributes($post, $postAttributes);
                $this->syncSchedule($post, $revision, $changedBy, $note, $postAttributes);
            }

            return $post->fresh();
        });
    }

    /**
     * @param  array<string, mixed>  $postAttributes
     */
    private function applyStatusChange(
        Post $post,
        ?PostRevision $revision,
        PostStatus $targetStatus,
        ?Model $changedBy,
        ?string $note,
        array $postAttributes
    ): void {
        $fromStatus = $this->resolveStatus($post->status);

        if ($targetStatus === PostStatus::Published) {
            if ($revision === null) {
                throw new InvalidArgumentException('revision is required when publishing.');
            }

            $publishedAt = $this->normalizeDate($postAttributes['published_at'] ?? null);
            $this->publisher->publish($post, $revision, $changedBy, $publishedAt, $note);

            return;
        }

        if ($targetStatus === PostStatus::Scheduled) {
            if ($revision === null) {
                throw new InvalidArgumentException('revision is required when scheduling.');
            }

            if ($changedBy === null) {
                throw new InvalidArgumentException('changedBy is required when scheduling.');
            }

            $scheduledAt = $this->normalizeDate($postAttributes['scheduled_at'] ?? null);
            if ($scheduledAt === null) {
                throw new InvalidArgumentException('scheduled_at is required when scheduling.');
            }

            $this->scheduler->schedule($post, $revision, $scheduledAt, $changedBy, $note);

            return;
        }

        $payload = [
            'status' => $targetStatus,
        ];

        if ($fromStatus === PostStatus::Scheduled) {
            $payload['scheduled_at'] = null;
            PostRevisionSchedule::where('post_id', $post->id)->delete();
        }

        if (array_key_exists('published_at', $postAttributes)) {
            $payload['published_at'] = $this->normalizeDate($postAttributes['published_at']);
        }

        $post->forceFill($payload)->save();

        PostStatusEvent::create([
            'post_id' => $post->id,
            'from_status' => $fromStatus,
            'to_status' => $targetStatus,
            'changed_by_user_id' => $changedBy?->getKey(),
            'note' => $note,
            'changed_at' => now(),
        ]);

        PostStatusChanged::dispatch($post, $fromStatus, $targetStatus, $changedBy);
    }

    /**
     * @param  array<string, mixed>  $postAttributes
     */
    private function applyDateAttributes(Post $post, array $postAttributes): void
    {
        $updates = [];

        if (array_key_exists('published_at', $postAttributes)) {
            $updates['published_at'] = $this->normalizeDate($postAttributes['published_at']);
        }

        if (array_key_exists('scheduled_at', $postAttributes)) {
            $updates['scheduled_at'] = $this->normalizeDate($postAttributes['scheduled_at']);
        }

        if ($updates !== []) {
            $post->forceFill($updates)->save();
        }
    }

    /**
     * @param  array<string, mixed>  $postAttributes
     */
    private function syncSchedule(
        Post $post,
        ?PostRevision $revision,
        ?Model $changedBy,
        ?string $note,
        array $postAttributes
    ): void {
        if ($this->resolveStatus($post->status) !== PostStatus::Scheduled) {
            return;
        }

        if ($revision === null && ! array_key_exists('scheduled_at', $postAttributes) && $note === null) {
            return;
        }

        if ($changedBy === null) {
            throw new InvalidArgumentException('changedBy is required when scheduling.');
        }

        $schedule = PostRevisionSchedule::firstOrNew([
            'post_id' => $post->id,
        ]);

        $revisionId = $revision !== null ? $revision->id : $schedule->revision_id;
        if ($revisionId === null) {
            throw new InvalidArgumentException('revision is required when scheduling.');
        }

        $schedule->fill([
            'revision_id' => $revisionId,
            'scheduled_by_user_id' => $changedBy->getKey(),
            'note' => $note,
        ])->save();

        if ($post->published_at !== null) {
            $post->forceFill(['published_at' => null])->save();
        }
    }

    /**
     * @param  array<string, mixed>  $revisionAttributes
     */
    private function createRevision(Post $post, array $revisionAttributes, ?Model $editor): PostRevision
    {
        $editorId = $revisionAttributes['editor_user_id'] ?? $editor?->getKey();
        if ($editorId === null) {
            throw new InvalidArgumentException('editor_user_id is required when creating a revision.');
        }

        $payload = array_merge($revisionAttributes, [
            'post_id' => $post->id,
            'editor_user_id' => $editorId,
        ]);

        return PostRevision::create($payload);
    }

    private function defaultStatus(): PostStatus
    {
        $status = config('toko.default_status', PostStatus::Draft);

        return $this->resolveStatus($status);
    }

    private function resolveStatus(PostStatus|int|string $status): PostStatus
    {
        return $status instanceof PostStatus ? $status : PostStatus::from((int) $status);
    }

    private function normalizeDate(DateTimeInterface|string|null $value): ?Carbon
    {
        if ($value === null) {
            return null;
        }

        if ($value instanceof Carbon) {
            return $value;
        }

        if ($value instanceof DateTimeInterface) {
            return Carbon::instance($value);
        }

        return Carbon::parse($value);
    }
}
