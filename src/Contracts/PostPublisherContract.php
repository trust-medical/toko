<?php

declare(strict_types=1);

namespace TrustMedical\Toko\Contracts;

use DateTimeInterface;
use Illuminate\Database\Eloquent\Model;
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
        ?DateTimeInterface $publishedAt = null,
        ?string $note = null
    ): PostRevisionPublish;
}
