<?php

declare(strict_types=1);

namespace Escolar\Library\Enums;

enum ReservationStatus: string
{
    case Queued = 'queued';
    case Available = 'available';
    case Fulfilled = 'fulfilled';
    case Expired = 'expired';
    case Cancelled = 'cancelled';

    public function label(): string
    {
        return match ($this) {
            self::Queued => 'Na fila',
            self::Available => 'Disponível para retirada',
            self::Fulfilled => 'Retirada',
            self::Expired => 'Expirada',
            self::Cancelled => 'Cancelada',
        };
    }
}
