<?php

declare(strict_types=1);

namespace TrustMedical\Toko\Contracts;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;
use TrustMedical\Toko\Models\Post;
use TrustMedical\Toko\Models\PostRevision;
use TrustMedical\Toko\Models\PostRevisionPublish;

interface PostPublisherContract
{
    /**
     * 公開処理を一括で実行する。
     */
    public function publish(
        Post $post,
        PostRevision $revision,
        ?Model $publishedBy = null,
        ?Carbon $publishedAt = null,
        ?string $note = null
    ): PostRevisionPublish;
}
