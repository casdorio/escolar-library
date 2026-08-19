<?php

declare(strict_types=1);

namespace Escolar\Library\Enums;

enum MaterialType: string
{
    case Livro = 'livro';
    case Periodico = 'periodico';
    case Jornal = 'jornal';
    case Dvd = 'dvd';
    case Mapa = 'mapa';
    case JogoPedagogico = 'jogo_pedagogico';
    case Tcc = 'tcc';
    case EbookLink = 'ebook_link';
    case MaterialDidatico = 'material_didatico';

    public function label(): string
    {
        return match ($this) {
            self::Livro => 'Livro',
            self::Periodico => 'Periódico/Revista',
            self::Jornal => 'Jornal',
            self::Dvd => 'DVD/Mídia',
            self::Mapa => 'Mapa',
            self::JogoPedagogico => 'Jogo pedagógico',
            self::Tcc => 'TCC/Monografia',
            self::EbookLink => 'E-book (link)',
            self::MaterialDidatico => 'Material didático',
        };
    }

    /** @return list<string> */
    public static function values(): array
    {
        return array_map(static fn (self $c): string => $c->value, self::cases());
    }
}
