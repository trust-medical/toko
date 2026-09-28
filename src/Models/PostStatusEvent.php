<?php

declare(strict_types=1);

namespace TrustMedical\Toko\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;
use TrustMedical\Toko\Database\Factories\PostStatusEventFactory;
use TrustMedical\Toko\Enums\PostStatus;
use TrustMedical\Toko\Models\Concerns\ResolvesUserModel;

/**
 * @property int $id
 * @property int $post_id
 * @property PostStatus|null $from_status
 * @property PostStatus $to_status
 * @property int|null $changed_by_user_id
 * @property string|null $note
 * @property Carbon $changed_at
 * @property-read Post $post
 * @property-read Model|null $changedBy
 *
 * @use HasFactory<PostStatusEventFactory>
 */
final class PostStatusEvent extends Model
{
    /** @use HasFactory<PostStatusEventFactory> */
    use HasFactory;

    use ResolvesUserModel;

    protected $table = 'post_status_events';

    // changed_atのみを使うためtimestampsは不要
    public $timestamps = false;

    protected $fillable = [
        'post_id',
        'from_status',
        'to_status',
        'changed_by_user_id',
        'note',
        'changed_at',
    ];

    protected $casts = [
        // 状態はenumとして扱う
        'from_status' => PostStatus::class,
        'to_status' => PostStatus::class,
        'changed_at' => 'datetime',
    ];

    /**
     * @return BelongsTo<Post, $this>
     */
    public function post(): BelongsTo
    {
        return $this->belongsTo(Post::class, 'post_id');
    }

    /**
     * @return BelongsTo<Model, $this>
     */
    public function changedBy(): BelongsTo
    {
        /** @var class-string<Model> $userModel */
        $userModel = $this->userModel();

        return $this->belongsTo($userModel, 'changed_by_user_id');
    }

    protected static function newFactory(): PostStatusEventFactory
    {
        return PostStatusEventFactory::new();
    }
}
