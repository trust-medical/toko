<?php

declare(strict_types=1);

namespace TrustMedical\Toko\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use TrustMedical\Toko\Database\Factories\PostRevisionScheduleFactory;
use TrustMedical\Toko\Models\Concerns\ResolvesUserModel;

/**
 * @property int $id
 * @property int $post_id
 * @property int $revision_id
 * @property int $scheduled_by_user_id
 * @property string|null $note
 * @property \Illuminate\Support\Carbon $created_at
 * @property \Illuminate\Support\Carbon $updated_at
 * @property-read Post $post
 * @property-read PostRevision $revision
 * @property-read Model|null $scheduledBy
 *
 * @use HasFactory<PostRevisionScheduleFactory>
 */
final class PostRevisionSchedule extends Model
{
    /** @use HasFactory<PostRevisionScheduleFactory> */
    use HasFactory;

    use ResolvesUserModel;

    protected $table = 'post_revision_schedules';

    protected $fillable = [
        'post_id',
        'revision_id',
        'scheduled_by_user_id',
        'note',
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
    public function scheduledBy(): BelongsTo
    {
        /** @var class-string<Model> $userModel */
        $userModel = $this->userModel();

        return $this->belongsTo($userModel, 'scheduled_by_user_id');
    }

    protected static function newFactory(): PostRevisionScheduleFactory
    {
        return PostRevisionScheduleFactory::new();
    }
}
