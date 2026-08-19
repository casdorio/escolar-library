<?php

declare(strict_types=1);

namespace Escolar\Library\Enums;

enum AudienceMode: string
{
    case Advisory = 'advisory';
    case Enforced = 'enforced';

    public function label(): string
    {
        return match ($this) {
            self::Advisory => 'Avisa e deixa passar',
            self::Enforced => 'Bloqueia',
        };
    }
}
