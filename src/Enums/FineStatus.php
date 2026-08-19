<?php

declare(strict_types=1);

namespace Escolar\Library\Enums;

enum FineStatus: string
{
    case Pending = 'pending';
    case Paid = 'paid';
    case Waived = 'waived';
    case Charged = 'charged';

    public function label(): string
    {
        return match ($this) {
            self::Pending => 'Pendente',
            self::Paid => 'Paga',
            self::Waived => 'Perdoada',
            self::Charged => 'Cobrança no Financeiro',
        };
    }
}
