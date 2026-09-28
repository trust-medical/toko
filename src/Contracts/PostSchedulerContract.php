<?php

declare(strict_types=1);

namespace TrustMedical\Toko\Contracts;

use DateTimeInterface;
use Illuminate\Database\Eloquent\Model;
use TrustMedical\Toko\Models\Post;
use TrustMedical\Toko\Models\PostRevision;
use TrustMedical\Toko\Models\PostRevisionSchedule;

interface PostSchedulerContract
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
    ): PostRevisionSchedule;
}
