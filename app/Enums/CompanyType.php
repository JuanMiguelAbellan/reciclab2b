<?php

namespace App\Enums;

enum CompanyType: string
{
    case Producer = 'producer';
    case Distributor = 'distributor';
    case Warehouse = 'warehouse';
    case Industry = 'industry';
    case Broker = 'broker';
    case Mixed = 'mixed';
    case Other = 'other';

    public function label(): string
    {
        return match ($this) {
            self::Producer => 'Productor',
            self::Distributor => 'Distribuidor',
            self::Warehouse => 'Almacén',
            self::Industry => 'Industria',
            self::Broker => 'Intermediario',
            self::Mixed => 'Mixta',
            self::Other => 'Otro',
        };
    }
}
