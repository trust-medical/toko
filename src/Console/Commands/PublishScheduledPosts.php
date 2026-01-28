<?php

declare(strict_types=1);

namespace TrustMedical\Toko\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;
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

        $schedules = PostRevisionSchedule::query()
            ->whereHas('post', function ($query) use ($now): void {
                $query->where('status', PostStatus::Scheduled)
                    ->whereNotNull('scheduled_at')
                    ->where('scheduled_at', '<=', $now);
            })
            ->with(['post', 'revision', 'scheduledBy'])
            ->orderBy('id')
            ->limit($limit)
            ->get();

        foreach ($schedules as $schedule) {
            DB::transaction(function () use ($schedule, $publisher, $now, &$processed): void {
                $locked = PostRevisionSchedule::query()
                    ->whereKey($schedule->id)
                    ->lockForUpdate()
                    ->first();

                if ($locked === null) {
                    return;
                }

                $locked->loadMissing(['revision', 'scheduledBy']);

                $post = Post::query()
                    ->whereKey($locked->post_id)
                    ->lockForUpdate()
                    ->first();

                if ($post === null) {
                    $locked->delete();

                    return;
                }

                if ($post->status !== PostStatus::Scheduled || $post->scheduled_at === null || $post->scheduled_at->greaterThan($now)) {
                    return;
                }

                $scheduledBy = $locked->scheduledBy;
                if ($scheduledBy === null) {
                    throw new InvalidArgumentException('scheduledBy user not found.');
                }

                $scheduledAt = $post->scheduled_at;
                $publisher->publish($post, $locked->revision, $scheduledBy, $scheduledAt, 'Publish scheduled post');
                $locked->delete();

                $processed++;
            });
        }

        if ($processed > 0) {
            $this->info("Published {$processed} scheduled post(s).");
        }

        return self::SUCCESS;
    }
}
