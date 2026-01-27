<?php

declare(strict_types=1);

namespace TrustMedical\Toko\Events;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use TrustMedical\Toko\Models\Post;
use TrustMedical\Toko\Models\PostRevision;
use TrustMedical\Toko\Models\PostRevisionPublish;

final class PostPublished
{
    use Dispatchable;
    use SerializesModels;

    public function __construct(
        public Post $post,
        public PostRevision $revision,
        public PostRevisionPublish $publish,
        public ?Model $publishedBy = null
    ) {}
}
