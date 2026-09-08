<?php

namespace App\Enums;

enum OrderStatus: string
{
    case Pending = 'pending';
    case Accepted = 'accepted';
    case Rejected = 'rejected';
    case Completed = 'completed';
    case Cancelled = 'cancelled';

    public function label(): string
    {
        return match ($this) {
            self::Pending => 'Pendiente',
            self::Accepted => 'Aceptado',
            self::Rejected => 'Rechazado',
            self::Completed => 'Completado',
            self::Cancelled => 'Cancelado',
        };
    }
}
