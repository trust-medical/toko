<?php

declare(strict_types=1);

namespace TrustMedical\Toko\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use TrustMedical\Toko\Database\Factories\PostRevisionFactory;
use TrustMedical\Toko\Models\Concerns\ResolvesUserModel;

/**
 * @property int $id
 * @property int $post_id
 * @property int $editor_user_id
 * @property string $title
 * @property string|null $excerpt
 * @property string|null $slug
 * @property array<string, mixed> $content_json
 * @property string|null $content_html
 * @property string $editor
 * @property string|null $editor_version
 * @property int $schema_version
 * @property string|null $change_note
 * @property \Illuminate\Support\Carbon $created_at
 * @property-read Post $post
 * @property-read Model $editorUser
 *
 * @use HasFactory<PostRevisionFactory>
 */
final class PostRevision extends Model
{
    /** @use HasFactory<PostRevisionFactory> */
    use HasFactory;

    use ResolvesUserModel;

    protected $table = 'post_revisions';

    // revisionは基本不変のためupdated_atを無効化
    public const UPDATED_AT = null;

    protected $fillable = [
        'post_id',
        'editor_user_id',
        'title',
        'excerpt',
        'slug',
        'content_json',
        'content_html',
        'editor',
        'editor_version',
        'schema_version',
        'change_note',
    ];

    protected $casts = [
        // TipTap JSONは配列として扱う
        'content_json' => 'array',
        'schema_version' => 'integer',
        'created_at' => 'datetime',
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
    public function editorUser(): BelongsTo
    {
        // カラム名editorと衝突しないようrelation名を分ける
        /** @var class-string<Model> $userModel */
        $userModel = $this->userModel();

        return $this->belongsTo($userModel, 'editor_user_id');
    }

    protected static function newFactory(): PostRevisionFactory
    {
        return PostRevisionFactory::new();
    }
}
