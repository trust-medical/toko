<?php

declare(strict_types=1);

namespace TrustMedical\Toko\Enums;

use TrustMedical\Toko\Contracts\HasLabel;

enum PostStatus: int implements HasLabel
{
    case Draft = 0;
    case Scheduled = 1;
    case Published = 2;
    case Archived = 3;

    public function getLabel(): string
    {
        return match ($this) {
            self::Draft => 'Draft',
            self::Scheduled => 'Scheduled',
            self::Published => 'Published',
            self::Archived => 'Archived',
        };
    }
}
