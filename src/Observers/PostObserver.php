<?php

declare(strict_types=1);

namespace TrustMedical\Toko\Observers;

use TrustMedical\Toko\Events\PostSlugChanged;
use TrustMedical\Toko\Models\Post;

final class PostObserver
{
    public function updating(Post $post): void
    {
        if (! $post->isDirty('slug')) {
            return;
        }

        $oldSlug = $post->getOriginal('slug');
        if ($oldSlug === null || $oldSlug === '') {
            return;
        }

        PostSlugChanged::dispatch($post, $oldSlug, (string) $post->slug);
    }
}
