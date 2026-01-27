<?php

declare(strict_types=1);

namespace TrustMedical\Toko\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use TrustMedical\Toko\Database\Factories\PostSlugHistoryFactory;

/**
 * @property int $id
 * @property int $post_id
 * @property string $old_slug
 * @property \Illuminate\Support\Carbon $created_at
 * @property-read Post $post
 *
 * @use HasFactory<PostSlugHistoryFactory>
 */
final class PostSlugHistory extends Model
{
    /** @use HasFactory<PostSlugHistoryFactory> */
    use HasFactory;

    protected $table = 'post_slug_histories';

    // created_atのみを持つためtimestampsは使わない
    public $timestamps = false;

    protected $fillable = [
        'post_id',
        'old_slug',
        'created_at',
    ];

    protected $casts = [
        'created_at' => 'datetime',
    ];

    /**
     * @return BelongsTo<Post, $this>
     */
    public function post(): BelongsTo
    {
        return $this->belongsTo(Post::class, 'post_id');
    }

    protected static function newFactory(): PostSlugHistoryFactory
    {
        return PostSlugHistoryFactory::new();
    }
}
