<?php

declare(strict_types=1);

namespace TrustMedical\Toko\Contracts;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;
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
        Carbon $scheduledAt,
        ?Model $scheduledBy = null,
        ?string $note = null
    ): PostRevisionSchedule;
}
