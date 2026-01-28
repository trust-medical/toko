<?php

declare(strict_types=1);

namespace TrustMedical\Toko\Contracts;

if (interface_exists(\Filament\Support\Contracts\HasColor::class)) {
    interface HasColor extends \Filament\Support\Contracts\HasColor {}
} else {
    interface HasColor
    {
        public function getColor(): string | array | null;
    }
}
