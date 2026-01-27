<?php

declare(strict_types=1);

namespace TrustMedical\Toko\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use TrustMedical\Toko\Database\Factories\PostRevisionPublishFactory;
use TrustMedical\Toko\Models\Concerns\ResolvesUserModel;

/**
 * @property int $id
 * @property int $post_id
 * @property int $revision_id
 * @property int $published_by_user_id
 * @property \Illuminate\Support\Carbon $published_at
 * @property-read Post $post
 * @property-read PostRevision $revision
 * @property-read Model $publishedBy
 *
 * @use HasFactory<PostRevisionPublishFactory>
 */
final class PostRevisionPublish extends Model
{
    /** @use HasFactory<PostRevisionPublishFactory> */
    use HasFactory;

    use ResolvesUserModel;

    protected $table = 'post_revision_publishes';

    // published_atのみを持つためtimestampsは使わない
    public $timestamps = false;

    protected $fillable = [
        'post_id',
        'revision_id',
        'published_by_user_id',
        'published_at',
    ];

    protected $casts = [
        'published_at' => 'datetime',
    ];

    /**
     * @return BelongsTo<Post, $this>
     */
    public function post(): BelongsTo
    {
        return $this->belongsTo(Post::class, 'post_id');
    }

    /**
     * @return BelongsTo<PostRevision, $this>
     */
    public function revision(): BelongsTo
    {
        return $this->belongsTo(PostRevision::class, 'revision_id');
    }

    /**
     * @return BelongsTo<Model, $this>
     */
    public function publishedBy(): BelongsTo
    {
        /** @var class-string<Model> $userModel */
        $userModel = $this->userModel();

        return $this->belongsTo($userModel, 'published_by_user_id');
    }

    protected static function newFactory(): PostRevisionPublishFactory
    {
        return PostRevisionPublishFactory::new();
    }
}
