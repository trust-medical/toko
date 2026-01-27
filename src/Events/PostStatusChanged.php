<?php

declare(strict_types=1);

namespace TrustMedical\Toko\Events;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use TrustMedical\Toko\Enums\PostStatus;
use TrustMedical\Toko\Models\Post;

final class PostStatusChanged
{
    use Dispatchable;
    use SerializesModels;

    public function __construct(
        public Post $post,
        public PostStatus $from,
        public PostStatus $to,
        public ?Model $changedBy = null
    ) {}
}
