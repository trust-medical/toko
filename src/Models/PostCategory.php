<?php

declare(strict_types=1);

namespace TrustMedical\Toko\Models;

use Closure;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Collection;
use TrustMedical\Toko\Database\Factories\PostCategoryFactory;
use TrustMedical\Toko\Support\CategoryTreeBuilder;

/**
 * @property int $id
 * @property int|null $parent_id
 * @property string $name
 * @property string $slug
 * @property int $sort_order
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read PostCategory|null $parent
 * @property-read \Illuminate\Database\Eloquent\Collection<int, PostCategory> $children
 * @property-read \Illuminate\Database\Eloquent\Collection<int, Post> $posts
 * @property-read int|null $published_posts_count
 * @property-read int|null $posts_count
 *
 * @use HasFactory<PostCategoryFactory>
 */
final class PostCategory extends Model
{
    /** @use HasFactory<PostCategoryFactory> */
    use HasFactory;

    protected $table = 'post_categories';

    protected $fillable = [
        'parent_id',
        'name',
        'slug',
        'sort_order',
    ];

    /**
     * @return BelongsTo<PostCategory, $this>
     */
    public function parent(): BelongsTo
    {
        return $this->belongsTo(self::class, 'parent_id');
    }

    /**
     * @return HasMany<PostCategory, $this>
     */
    public function children(): HasMany
    {
        return $this->hasMany(self::class, 'parent_id');
    }

    /**
     * @return HasMany<Post, $this>
     */
    public function posts(): HasMany
    {
        return $this->hasMany(Post::class, 'category_id');
    }

    /**
     * @param  Builder<self>  $query
     * @return Builder<self>
     */
    public function scopeRoots(Builder $query): Builder
    {
        return $query->whereNull('parent_id');
    }

    /**
     * @param  Builder<self>  $query
     * @return Builder<self>
     */
    public function scopeOrdered(Builder $query): Builder
    {
        // sort_order優先、同値はIDで安定化
        return $query->orderBy('sort_order')->orderBy('id');
    }

    /**
     * 公開記事付きカテゴリツリーを取得する。
     *
     * @param  Closure|null  $postsQuery  追加条件を付けたい場合のコールバック
     * @return Collection<int, PostCategory>
     */
    public static function treeWithPublishedPosts(?Closure $postsQuery = null): Collection
    {
        return CategoryTreeBuilder::build($postsQuery, true);
    }

    /**
     * 公開・非公開を問わず記事を含むカテゴリツリーを取得する。
     *
     * @param  Closure|null  $postsQuery  追加条件を付けたい場合のコールバック
     * @return Collection<int, PostCategory>
     */
    public static function treeWithPosts(?Closure $postsQuery = null): Collection
    {
        return CategoryTreeBuilder::build($postsQuery, false);
    }

    protected static function newFactory(): PostCategoryFactory
    {
        return PostCategoryFactory::new();
    }
}
