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

        // 現役のslugは履歴から外す
        PostSlugHistory::query()->where('old_slug', $event->newSlug)->delete();

        // 同じ旧slugは最新の持ち主で上書きする
        PostSlugHistory::updateOrCreate(
            ['old_slug' => $event->oldSlug],
            ['post_id' => $event->post->id, 'created_at' => now()]
        );
    }
}
