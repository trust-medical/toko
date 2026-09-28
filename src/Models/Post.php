<?php

declare(strict_types=1);

namespace TrustMedical\Toko\Models;

use DateTimeInterface;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Carbon;
use TrustMedical\Toko\Database\Factories\PostFactory;
use TrustMedical\Toko\Enums\PostStatus;
use TrustMedical\Toko\Models\Concerns\ResolvesUserModel;

/**
 * @property int $id
 * @property int $author_user_id
 * @property int $category_id
 * @property string $title
 * @property string|null $excerpt
 * @property string $slug
 * @property PostStatus $status
 * @property Carbon|null $published_at
 * @property Carbon|null $scheduled_at
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read Model $author
 * @property-read PostCategory $category
 * @property-read Collection<int, PostRevision> $revisions
 * @property-read Collection<int, PostRevisionPublish> $revisionPublishes
 * @property-read PostRevisionSchedule|null $revisionSchedule
 * @property-read Collection<int, PostStatusEvent> $statusEvents
 * @property-read Collection<int, PostSlugHistory> $slugHistories
 * @property-read PostRevisionPublish|null $latestPublishedRevisionPublish
 *
 * @use HasFactory<PostFactory>
 */
final class Post extends Model
{
    /** @use HasFactory<PostFactory> */
    use HasFactory;

    use ResolvesUserModel;

    protected $table = 'posts';

    protected $fillable = [
        'author_user_id',
        'category_id',
        'title',
        'excerpt',
        'slug',
        'status',
        'published_at',
        'scheduled_at',
    ];

    // statusはenum、published_at/scheduled_atはDateTimeキャスト
    protected $casts = [
        'status' => PostStatus::class,
        'published_at' => 'datetime',
        'scheduled_at' => 'datetime',
    ];

    /**
     * @return BelongsTo<Model, $this>
     */
    public function author(): BelongsTo
    {
        /** @var class-string<Model> $userModel */
        $userModel = $this->userModel();

        return $this->belongsTo($userModel, 'author_user_id');
    }

    /**
     * @return BelongsTo<PostCategory, $this>
     */
    public function category(): BelongsTo
    {
        return $this->belongsTo(PostCategory::class, 'category_id');
    }

    /**
     * @return HasMany<PostRevision, $this>
     */
    public function revisions(): HasMany
    {
        return $this->hasMany(PostRevision::class, 'post_id');
    }

    /**
     * @return HasMany<PostRevisionPublish, $this>
     */
    public function revisionPublishes(): HasMany
    {
        return $this->hasMany(PostRevisionPublish::class, 'post_id');
    }

    /**
     * @return HasOne<PostRevisionSchedule, $this>
     */
    public function revisionSchedule(): HasOne
    {
        return $this->hasOne(PostRevisionSchedule::class, 'post_id');
    }

    /**
     * @return HasOne<PostRevisionPublish, $this>
     */
    public function latestPublishedRevisionPublish(): HasOne
    {
        return $this->hasOne(PostRevisionPublish::class, 'post_id')->latestOfMany('published_at');
    }

    public function latestPublishedRevision(): ?PostRevision
    {
        $publish = $this->latestPublishedRevisionPublish()
            ->with('revision')
            ->first();

        return $publish?->revision;
    }

    /**
     * @return HasMany<PostStatusEvent, $this>
     */
    public function statusEvents(): HasMany
    {
        return $this->hasMany(PostStatusEvent::class, 'post_id');
    }

    /**
     * @return HasMany<PostSlugHistory, $this>
     */
    public function slugHistories(): HasMany
    {
        return $this->hasMany(PostSlugHistory::class, 'post_id');
    }

    /**
     * @param  Builder<self>  $query
     * @return Builder<self>
     */
    public function scopeStatus(Builder $query, PostStatus|int $status): Builder
    {
        // enum/数値のどちらでも受けられるように吸収
        $value = $status instanceof PostStatus ? $status->value : $status;

        return $query->where('status', $value);
    }

    /**
     * @param  Builder<self>  $query
     * @return Builder<self>
     */
    public function scopeDraft(Builder $query): Builder
    {
        return $this->scopeStatus($query, PostStatus::Draft);
    }

    /**
     * @param  Builder<self>  $query
     * @return Builder<self>
     */
    public function scopeScheduled(Builder $query): Builder
    {
        return $this->scopeStatus($query, PostStatus::Scheduled);
    }

    /**
     * @param  Builder<self>  $query
     * @return Builder<self>
     */
    public function scopePublished(Builder $query): Builder
    {
        return $this->scopeStatus($query, PostStatus::Published);
    }

    /**
     * @param  Builder<self>  $query
     * @return Builder<self>
     */
    public function scopeArchived(Builder $query): Builder
    {
        return $this->scopeStatus($query, PostStatus::Archived);
    }

    /**
     * @param  Builder<self>  $query
     * @return Builder<self>
     */
    public function scopeByAuthor(Builder $query, int $userId): Builder
    {
        return $query->where('author_user_id', $userId);
    }

    /**
     * @param  Builder<self>  $query
     * @return Builder<self>
     */
    public function scopeInCategory(Builder $query, int $categoryId): Builder
    {
        return $query->where('category_id', $categoryId);
    }

    /**
     * @param  Builder<self>  $query
     * @return Builder<self>
     */
    public function scopePublishedAt(Builder $query, ?DateTimeInterface $at = null): Builder
    {
        $at ??= now();

        $this->scopePublished($query);

        return $query
            ->whereNotNull('published_at')
            ->where('published_at', '<=', $at);
    }

    /**
     * @param  Builder<self>  $query
     * @return Builder<self>
     */
    public function scopeScheduledBetween(Builder $query, DateTimeInterface $from, DateTimeInterface $to): Builder
    {
        $this->scopeScheduled($query);

        return $query
            ->whereNotNull('scheduled_at')
            ->whereBetween('scheduled_at', [$from, $to]);
    }

    /**
     * @param  Builder<self>  $query
     * @return Builder<self>
     */
    public function scopeVisible(Builder $query, ?DateTimeInterface $at = null): Builder
    {
        return $this->scopePublishedAt($query, $at);
    }

    protected static function newFactory(): PostFactory
    {
        return PostFactory::new();
    }
}
