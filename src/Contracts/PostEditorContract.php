<?php

declare(strict_types=1);

namespace TrustMedical\Toko\Contracts;

use Illuminate\Database\Eloquent\Model;
use TrustMedical\Toko\Models\Post;

interface PostEditorContract
{
    /**
     * 記事を作成し、初期リビジョンも作成する。
     *
     * @param  array<string, mixed>  $postAttributes
     * @param  array<string, mixed>  $revisionAttributes
     */
    public function create(
        array $postAttributes,
        array $revisionAttributes,
        ?Model $author = null,
        ?Model $editor = null,
        ?string $note = null
    ): Post;

    /**
     * 記事を更新し、必要に応じてリビジョンを作成する。
     *
     * @param  array<string, mixed>  $postAttributes
     * @param  array<string, mixed>  $revisionAttributes
     */
    public function update(
        Post $post,
        array $postAttributes = [],
        array $revisionAttributes = [],
        ?Model $changedBy = null,
        ?string $note = null
    ): Post;
}
