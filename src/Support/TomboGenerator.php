<?php

declare(strict_types=1);

namespace Escolar\Library\Support;

use Escolar\Library\Models\LibrarySettings;
use Illuminate\Support\Facades\DB;

/**
 * Gera o próximo tombo (patrimônio) do exemplar, incrementando o contador da
 * escola de forma atômica (lock na linha de configuração — evita corrida
 * quando dois exemplares são cadastrados ao mesmo tempo).
 */
class TomboGenerator
{
    public function next(int $schoolId): string
    {
        return DB::transaction(function () use ($schoolId): string {
            $settings = LibrarySettings::where('school_id', $schoolId)->lockForUpdate()->first();
            if (! $settings) {
                $settings = LibrarySettings::forSchool($schoolId);
            }

            $sequence = $settings->next_tombo_sequence;
            $settings->next_tombo_sequence = $sequence + 1;
            $settings->save();

            $prefix = $settings->tombo_prefix ?: '';

            return $prefix.str_pad((string) $sequence, 6, '0', STR_PAD_LEFT);
        });
    }
}
