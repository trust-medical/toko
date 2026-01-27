<?php

declare(strict_types=1);

namespace TrustMedical\Toko\Listeners;

use TrustMedical\Toko\Events\PostSlugChanged;
use TrustMedical\Toko\Models\PostSlugHistory;

final class WriteSlugHistory
{
    public function handle(PostSlugChanged $event): void
    {
        if (! config('toko.slug_history.enabled', true)) {
            return;
        }

        // 既に履歴がある場合は重複を避ける
        $exists = PostSlugHistory::query()->where('old_slug', $event->oldSlug)->exists();
        if ($exists) {
            return;
        }

        PostSlugHistory::create([
            'post_id' => $event->post->id,
            'old_slug' => $event->oldSlug,
        ]);
    }
}
