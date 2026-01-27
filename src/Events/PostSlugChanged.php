<?php

declare(strict_types=1);

namespace TrustMedical\Toko\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use TrustMedical\Toko\Models\Post;

final class PostSlugChanged
{
    use Dispatchable;
    use SerializesModels;

    public function __construct(
        public Post $post,
        public string $oldSlug,
        public string $newSlug
    ) {}
}
