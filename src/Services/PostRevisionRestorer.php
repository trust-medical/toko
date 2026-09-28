<?php

declare(strict_types=1);

namespace TrustMedical\Toko\Services;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;
use TrustMedical\Toko\Contracts\PostRevisionRestorerContract;
use TrustMedical\Toko\Enums\PostStatus;
use TrustMedical\Toko\Models\Post;
use TrustMedical\Toko\Models\PostRevision;

final class PostRevisionRestorer implements PostRevisionRestorerContract
{
    /**
     * 指定した revision を復元し、新しい revision として保存する。
     */
    public function restore(
        Post $post,
        PostRevision $revision,
        ?Model $restoredBy = null,
        ?string $note = null
    ): PostRevision {
        if ($revision->post_id !== $post->id) {
            throw new InvalidArgumentException('Revision does not belong to the given post.');
        }

        $restoredById = $restoredBy?->getKey();
        if ($restoredBy !== null && $restoredById === null) {
            throw new InvalidArgumentException('restoredBy must be a persisted model.');
        }

        // DBの既定値を含む保存済みの値から復元する
        $revision = $revision->fresh() ?? $revision;
        $editorId = $restoredById ?? $revision->editor_user_id;

        return DB::transaction(function () use ($post, $revision, $editorId, $note): PostRevision {
            $restored = PostRevision::create([
                'post_id' => $post->id,
                'editor_user_id' => $editorId,
                'title' => $revision->title,
                'excerpt' => $revision->excerpt,
                'slug' => $revision->slug,
                'content_json' => $revision->content_json,
                'content_html' => $revision->content_html,
                'editor' => $revision->editor,
                'editor_version' => $revision->editor_version,
                'schema_version' => $revision->schema_version,
                'change_note' => $note ?? ('Restore from revision #'.$revision->id),
            ]);

            // 公開中の記事は公開時にのみ反映するため、現在値は更新しない
            if ($post->status !== PostStatus::Published) {
                $post->forceFill([
                    'title' => $revision->title ?? $post->title,
                    'excerpt' => $revision->excerpt ?? $post->excerpt,
                    'slug' => $revision->slug ?? $post->slug,
                ])->save();
            }

            return $restored;
        });
    }
}
