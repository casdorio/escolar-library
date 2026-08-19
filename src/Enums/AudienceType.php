<?php

declare(strict_types=1);

namespace Escolar\Library\Enums;

/** Público-alvo por obra — ver PLANO-BIBLIOTECA.md §2.1-A. */
enum AudienceType: string
{
    case Grade = 'grade'; // série/ano
    case ClassRoom = 'class'; // turma específica
    case Profile = 'profile'; // perfil de leitor

    public function label(): string
    {
        return match ($this) {
            self::Grade => 'Série/ano',
            self::ClassRoom => 'Turma',
            self::Profile => 'Perfil de leitor',
        };
    }
}
