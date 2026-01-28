<?php

declare(strict_types=1);

namespace TrustMedical\Toko\Enums;

use TrustMedical\Toko\Contracts\HasColor;
use TrustMedical\Toko\Contracts\HasLabel;

enum PostStatus: int implements HasColor, HasLabel
{
    case Draft = 0;
    case Scheduled = 1;
    case Published = 2;
    case Archived = 3;

    public function getLabel(): string
    {
        $key = 'toko::post-status.'.strtolower($this->name);
        $label = __($key);

        return $label === $key
            ? match ($this) {
                self::Draft => 'Draft',
                self::Scheduled => 'Scheduled',
                self::Published => 'Published',
                self::Archived => 'Archived',
            }
        : $label;
    }

    public function getColor(): string
    {
        return match ($this) {
            self::Draft => 'gray',
            self::Scheduled => 'warning',
            self::Published => 'success',
            self::Archived => 'danger',
        };
    }
}
