<?php

declare(strict_types=1);

namespace Escolar\Library\Enums;

enum LoanStatus: string
{
    case Open = 'open';
    case Returned = 'returned';
    case Late = 'late';
    case Lost = 'lost';

    public function label(): string
    {
        return match ($this) {
            self::Open => 'Em aberto',
            self::Returned => 'Devolvido',
            self::Late => 'Atrasado',
            self::Lost => 'Perdido',
        };
    }
}
