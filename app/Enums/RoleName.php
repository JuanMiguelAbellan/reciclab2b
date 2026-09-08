<?php

namespace App\Enums;

enum RoleName: string
{
    case SuperAdmin = 'superadmin';
    case Admin = 'admin';
    case Producer = 'producer';
    case Buyer = 'buyer';

    public function label(): string
    {
        return match ($this) {
            self::SuperAdmin => 'Superadministrador',
            self::Admin => 'Administrador',
            self::Producer => 'Productor',
            self::Buyer => 'Comprador',
        };
    }
}
