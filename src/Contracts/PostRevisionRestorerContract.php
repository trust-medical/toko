<?php

declare(strict_types=1);

namespace TrustMedical\Toko\Contracts;

use Illuminate\Database\Eloquent\Model;
use TrustMedical\Toko\Models\Post;
use TrustMedical\Toko\Models\PostRevision;

interface PostRevisionRestorerContract
{
    /**
     * 指定した revision を復元し、新しい revision として保存する。
     */
    public function restore(
        Post $post,
        PostRevision $revision,
        ?Model $restoredBy = null,
        ?string $note = null
    ): PostRevision;
}
