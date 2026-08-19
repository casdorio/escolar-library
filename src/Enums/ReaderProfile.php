<?php

declare(strict_types=1);

namespace Escolar\Library\Enums;

enum ReaderProfile: string
{
    case Student = 'student';
    case Teacher = 'teacher';
    case Staff = 'staff';
    case Guardian = 'guardian';
    case External = 'external';

    public function label(): string
    {
        return match ($this) {
            self::Student => 'Aluno',
            self::Teacher => 'Professor',
            self::Staff => 'Funcionário',
            self::Guardian => 'Responsável',
            self::External => 'Externo',
        };
    }

    /** @return list<string> */
    public static function values(): array
    {
        return array_map(static fn (self $c): string => $c->value, self::cases());
    }
}
