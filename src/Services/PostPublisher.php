<?php

declare(strict_types=1);

namespace TrustMedical\Toko\Services;

use DateTimeInterface;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;
use TrustMedical\Toko\Contracts\PostPublisherContract;
use TrustMedical\Toko\Enums\PostStatus;
use TrustMedical\Toko\Events\PostPublished;
use TrustMedical\Toko\Events\PostStatusChanged;
use TrustMedical\Toko\Models\Post;
use TrustMedical\Toko\Models\PostRevision;
use TrustMedical\Toko\Models\PostRevisionPublish;
use TrustMedical\Toko\Models\PostRevisionSchedule;
use TrustMedical\Toko\Models\PostStatusEvent;

final class PostPublisher implements PostPublisherContract
{
    /**
     * 公開処理を一括で実行する。
     */
    public function publish(
        Post $post,
        PostRevision $revision,
        ?Model $publishedBy = null,
        ?DateTimeInterface $publishedAt = null,
        ?string $note = null
    ): PostRevisionPublish {
        if ($revision->post_id !== $post->id) {
            throw new InvalidArgumentException('Revision does not belong to the given post.');
        }

        if (config('toko.publishing.require_content_html', true) && ($revision->content_html === null || $revision->content_html === '')) {
            throw new InvalidArgumentException('content_html is required when publishing.');
        }

        $alreadyPublished = PostRevisionPublish::query()
            ->where('post_id', $post->id)
            ->where('revision_id', $revision->id)
            ->exists();
        if ($alreadyPublished) {
            throw new InvalidArgumentException('Revision has already been published. Restore it as a new revision to publish again.');
        }

        $publishedAt = $publishedAt !== null ? Carbon::instance($publishedAt) : now();
        $fromStatus = $post->status;

        return DB::transaction(function () use ($post, $revision, $publishedBy, $publishedAt, $note, $fromStatus): PostRevisionPublish {
            $publish = PostRevisionPublish::create([
                'post_id' => $post->id,
                'revision_id' => $revision->id,
                'published_by_user_id' => $publishedBy?->getKey(),
                'published_at' => $publishedAt,
            ]);

            $post->forceFill([
                'status' => PostStatus::Published,
                'published_at' => $publishedAt,
                'title' => $revision->title ?? $post->title,
                'excerpt' => $revision->excerpt ?? $post->excerpt,
                'slug' => $revision->slug ?? $post->slug,
            ])->save();

            PostRevisionSchedule::where('post_id', $post->id)->delete();

            // 公開中の記事に新しいrevisionを公開した場合はステータス変更として扱わない
            if ($fromStatus !== PostStatus::Published) {
                PostStatusEvent::create([
                    'post_id' => $post->id,
                    'from_status' => $fromStatus,
                    'to_status' => PostStatus::Published,
                    'changed_by_user_id' => $publishedBy?->getKey(),
                    'note' => $note,
                    'changed_at' => $publishedAt,
                ]);

                PostStatusChanged::dispatch($post, $fromStatus, PostStatus::Published, $publishedBy);
            }
            PostPublished::dispatch($post, $revision, $publish, $publishedBy);

            return $publish;
        });
    }
}
