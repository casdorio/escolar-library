<?php

declare(strict_types=1);

namespace Escolar\Library\Enums;

/** Política de atraso — a escola escolhe, o sistema não decide. PLANO-BIBLIOTECA.md §2.1-K. */
enum OverduePolicy: string
{
    case None = 'none';
    case Suspension = 'suspension';
    case Fine = 'fine';
    case FineAndSuspension = 'fine_and_suspension';

    public function label(): string
    {
        return match ($this) {
            self::None => 'Nenhuma ação além do aviso',
            self::Suspension => 'Suspensão do leitor',
            self::Fine => 'Multa',
            self::FineAndSuspension => 'Multa e suspensão',
        };
    }

    public function hasFine(): bool
    {
        return $this === self::Fine || $this === self::FineAndSuspension;
    }

    public function hasSuspension(): bool
    {
        return $this === self::Suspension || $this === self::FineAndSuspension;
    }

    /** @return list<string> */
    public static function values(): array
    {
        return array_map(static fn (self $c): string => $c->value, self::cases());
    }
}
