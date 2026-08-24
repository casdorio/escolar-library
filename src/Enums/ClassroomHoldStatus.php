<?php

declare(strict_types=1);

namespace Escolar\Library\Enums;

enum ClassroomHoldStatus: string
{
    case Active = 'active';
    case Released = 'released';
    case Cancelled = 'cancelled';

    public function label(): string
    {
        return match ($this) {
            self::Active => 'Ativa',
            self::Released => 'Liberada',
            self::Cancelled => 'Cancelada',
        };
    }
}
