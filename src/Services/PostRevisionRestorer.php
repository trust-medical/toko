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

            $status = $post->status instanceof PostStatus ? $post->status : PostStatus::from((int) $post->status);
            if ($status !== PostStatus::Published) {
                $post->forceFill([
                    'title' => $revision->title,
                    'excerpt' => $revision->excerpt,
                    'slug' => $revision->slug,
                ])->save();
            }

            return $restored;
        });
    }
}
