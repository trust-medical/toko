<?php

declare(strict_types=1);

namespace TrustMedical\Toko\Observers;

use TrustMedical\Toko\Events\PostSlugChanged;
use TrustMedical\Toko\Models\Post;

final class PostObserver
{
    public function updated(Post $post): void
    {
        // 保存成功後に通知し、更新失敗時に履歴だけ残らないようにする
        if (! $post->wasChanged('slug')) {
            return;
        }

        $oldSlug = $post->getOriginal('slug');
        if ($oldSlug === null || $oldSlug === '') {
            return;
        }

        PostSlugChanged::dispatch($post, (string) $oldSlug, (string) $post->slug);
    }
}
