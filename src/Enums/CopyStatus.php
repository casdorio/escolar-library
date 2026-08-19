<?php

declare(strict_types=1);

namespace Escolar\Library\Enums;

enum CopyStatus: string
{
    case Available = 'available';
    case Loaned = 'loaned';
    case Reserved = 'reserved';
    case Repair = 'repair';
    case Lost = 'lost';
    case Discarded = 'discarded';

    public function label(): string
    {
        return match ($this) {
            self::Available => 'Disponível',
            self::Loaned => 'Emprestado',
            self::Reserved => 'Reservado',
            self::Repair => 'Em conserto',
            self::Lost => 'Perdido',
            self::Discarded => 'Baixado',
        };
    }

    /** Acento semântico do design system (§5.0.5 do plano). */
    public function accent(): string
    {
        return match ($this) {
            self::Available => 'success',
            self::Loaned => 'info',
            self::Reserved => 'warning',
            self::Repair, self::Lost => 'danger',
            self::Discarded => 'neutral',
        };
    }

    /** @return list<string> */
    public static function values(): array
    {
        return array_map(static fn (self $c): string => $c->value, self::cases());
    }
}
