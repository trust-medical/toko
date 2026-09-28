<?php

declare(strict_types=1);

namespace TrustMedical\Toko\Services;

use DateTimeInterface;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;
use TrustMedical\Toko\Contracts\PostSchedulerContract;
use TrustMedical\Toko\Enums\PostStatus;
use TrustMedical\Toko\Events\PostStatusChanged;
use TrustMedical\Toko\Models\Post;
use TrustMedical\Toko\Models\PostRevision;
use TrustMedical\Toko\Models\PostRevisionSchedule;
use TrustMedical\Toko\Models\PostStatusEvent;

final class PostScheduler implements PostSchedulerContract
{
    /**
     * 予約公開を登録する。
     */
    public function schedule(
        Post $post,
        PostRevision $revision,
        DateTimeInterface $scheduledAt,
        ?Model $scheduledBy = null,
        ?string $note = null
    ): PostRevisionSchedule {
        if ($revision->post_id !== $post->id) {
            throw new InvalidArgumentException('Revision does not belong to the given post.');
        }

        if ($scheduledBy === null) {
            throw new InvalidArgumentException('scheduledBy is required when scheduling.');
        }

        if (config('toko.publishing.require_content_html', true) && ($revision->content_html === null || $revision->content_html === '')) {
            throw new InvalidArgumentException('content_html is required when scheduling.');
        }

        $scheduledAt = Carbon::instance($scheduledAt);
        $fromStatus = $post->status;
        if ($fromStatus === PostStatus::Published) {
            throw new InvalidArgumentException('Cannot schedule a published post.');
        }

        return DB::transaction(function () use ($post, $revision, $scheduledAt, $scheduledBy, $note, $fromStatus): PostRevisionSchedule {
            $schedule = PostRevisionSchedule::updateOrCreate(
                ['post_id' => $post->id],
                [
                    'revision_id' => $revision->id,
                    'scheduled_by_user_id' => $scheduledBy->getKey(),
                    'note' => $note,
                ]
            );

            $post->forceFill([
                'status' => PostStatus::Scheduled,
                'published_at' => null,
                'scheduled_at' => $scheduledAt,
            ])->save();

            // 予約の差し替えはステータス変更として扱わない
            if ($fromStatus !== PostStatus::Scheduled) {
                PostStatusEvent::create([
                    'post_id' => $post->id,
                    'from_status' => $fromStatus,
                    'to_status' => PostStatus::Scheduled,
                    'changed_by_user_id' => $scheduledBy->getKey(),
                    'note' => $note,
                    'changed_at' => now(),
                ]);

                PostStatusChanged::dispatch($post, $fromStatus, PostStatus::Scheduled, $scheduledBy);
            }

            return $schedule;
        });
    }
}
