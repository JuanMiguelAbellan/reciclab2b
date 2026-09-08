<?php

namespace App\Enums;

enum OfferStatus: string
{
    case Draft = 'draft';
    case Published = 'published';
    case Paused = 'paused';
    case Closed = 'closed';

    public function label(): string
    {
        return match ($this) {
            self::Draft => 'Borrador',
            self::Published => 'Publicada',
            self::Paused => 'Pausada',
            self::Closed => 'Cerrada',
        };
    }
}
