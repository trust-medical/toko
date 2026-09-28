<?php

declare(strict_types=1);

namespace TrustMedical\Toko\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Throwable;
use TrustMedical\Toko\Contracts\PostPublisherContract;
use TrustMedical\Toko\Enums\PostStatus;
use TrustMedical\Toko\Models\Post;
use TrustMedical\Toko\Models\PostRevisionSchedule;

final class PublishScheduledPosts extends Command
{
    protected $signature = 'toko:publish-scheduled {--limit=100 : 一度に処理する件数}';

    protected $description = 'Publish scheduled posts whose scheduled_at is due.';

    public function handle(PostPublisherContract $publisher): int
    {
        $now = now();
        $limit = max(1, (int) $this->option('limit'));
        $processed = 0;
        $failed = 0;

        // 予約日時が古い順に処理する
        $scheduleIds = PostRevisionSchedule::query()
            ->join('posts', 'posts.id', '=', 'post_revision_schedules.post_id')
            ->where('posts.status', PostStatus::Scheduled->value)
            ->whereNotNull('posts.scheduled_at')
            ->where('posts.scheduled_at', '<=', $now)
            ->orderBy('posts.scheduled_at')
            ->orderBy('post_revision_schedules.id')
            ->limit($limit)
            ->pluck('post_revision_schedules.id');

        foreach ($scheduleIds as $scheduleId) {
            try {
                $published = DB::transaction(function () use ($scheduleId, $publisher, $now): bool {
                    $schedule = PostRevisionSchedule::query()
                        ->whereKey($scheduleId)
                        ->lockForUpdate()
                        ->first();

                    if ($schedule === null) {
                        return false;
                    }

                    $post = Post::query()
                        ->whereKey($schedule->post_id)
                        ->lockForUpdate()
                        ->first();

                    if ($post === null) {
                        $schedule->delete();

                        return false;
                    }

                    if ($post->status !== PostStatus::Scheduled || $post->scheduled_at === null || $post->scheduled_at->greaterThan($now)) {
                        return false;
                    }

                    // 予約者が取得できない場合(論理削除等)はシステムによる公開として扱う
                    $publisher->publish($post, $schedule->revision, $schedule->scheduledBy, $post->scheduled_at, 'Publish scheduled post');

                    return true;
                });
            } catch (Throwable $e) {
                report($e);
                $this->error("Failed to publish schedule #{$scheduleId}: {$e->getMessage()}");
                $failed++;

                continue;
            }

            if ($published) {
                $processed++;
            }
        }

        if ($processed > 0) {
            $this->info("Published {$processed} scheduled post(s).");
        }

        return $failed > 0 ? self::FAILURE : self::SUCCESS;
    }
}
