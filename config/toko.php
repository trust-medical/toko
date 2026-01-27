<?php

declare(strict_types=1);

use TrustMedical\Toko\Enums\PostStatus;

return [
    'default_status' => PostStatus::Draft,

    'slug_history' => [
        'enabled' => true,
    ],

    'publishing' => [
        // 公開時にcontent_htmlが必須か
        'require_content_html' => true,
    ],
];
