<?php

namespace App\Enums;

enum MaterialType: string
{
    case Cardboard = 'carton';
    case Plastic = 'plastico';

    public function label(): string
    {
        return match ($this) {
            self::Cardboard => 'Cardboard',
            self::Plastic => 'Plastic',
        };
    }
}
