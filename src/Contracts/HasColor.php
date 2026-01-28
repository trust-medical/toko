<?php

declare(strict_types=1);

namespace TrustMedical\Toko\Contracts;

if (interface_exists(\Filament\Support\Contracts\HasColor::class)) {
    interface HasColor extends \Filament\Support\Contracts\HasColor {}
} else {
    interface HasColor
    {
        /**
         * @return string|array<string>|null
         */
        public function getColor(): string|array|null;
    }
}
