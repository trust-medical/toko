<?php

declare(strict_types=1);

namespace TrustMedical\Toko\Support;

use Closure;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Support\Collection;
use TrustMedical\Toko\Models\PostCategory;

final class CategoryTreeBuilder
{
    /**
     * カテゴリツリーを取得する。
     *
     * @param  Closure|null  $postsQuery  追加条件を付けたい場合のコールバック
     * @param  bool  $onlyPublished  trueなら公開済みのみ、falseなら全件
     * @return Collection<int, PostCategory>
     */
    public static function build(?Closure $postsQuery = null, bool $onlyPublished = true): Collection
    {
        $postsQuery ??= static function ($query): void {
            // no-op
        };

        $countAlias = $onlyPublished ? 'published_posts_count' : 'posts_count';

        $categories = PostCategory::query()
            ->ordered()
            ->with([
                'posts' => function ($query) use ($postsQuery, $onlyPublished): void {
                    if ($onlyPublished) {
                        $query->published()->orderByDesc('published_at');
                    } else {
                        $query->orderByDesc('created_at');
                    }
                    $postsQuery($query);
                },
            ])
            ->withCount([
                'posts as '.$countAlias => function ($query) use ($postsQuery, $onlyPublished): void {
                    if ($onlyPublished) {
                        $query->published();
                    }
                    $postsQuery($query);
                },
            ])
            ->get();

        return self::buildFromCollection($categories);
    }

    /**
     * 取得済みのカテゴリコレクションからツリー構造を構築する。
     *
     * @param  EloquentCollection<int, PostCategory>  $categories
     * @return Collection<int, PostCategory>
     */
    public static function buildFromCollection(EloquentCollection $categories): Collection
    {
        $grouped = $categories->groupBy('parent_id');

        $buildTree = function ($parentId) use (&$buildTree, $grouped): Collection {
            return ($grouped[$parentId] ?? collect())
                ->map(function (PostCategory $category) use (&$buildTree): PostCategory {
                    $category->setRelation('children', $buildTree($category->id));

                    return $category;
                })
                ->values();
        };

        return $buildTree(null);
    }
}
