<?php

declare(strict_types=1);

namespace TrustMedical\Toko\Contracts;

if (interface_exists(\Filament\Support\Contracts\HasLabel::class)) {
    interface HasLabel extends \Filament\Support\Contracts\HasLabel {}
} else {
    interface HasLabel
    {
        public function getLabel(): string;
    }
}
