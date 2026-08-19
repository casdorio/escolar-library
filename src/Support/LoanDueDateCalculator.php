<?php

declare(strict_types=1);

namespace Escolar\Library\Support;

use Carbon\CarbonImmutable;
use Escolar\Library\Models\LibrarySettings;

/**
 * Calcula o vencimento do empréstimo a partir do perfil do leitor.
 *
 * `use_school_calendar` (pular feriados/recessos via `school_calendar_events`)
 * é a versão completa do plano — fica para quando a Circulação precisar dela
 * de fato; por ora soma dias corridos, que já cobre a operação do dia a dia.
 */
class LoanDueDateCalculator
{
    public function calculate(int $schoolId, string $profile, ?CarbonImmutable $from = null): CarbonImmutable
    {
        $settings = LibrarySettings::forSchool($schoolId);
        $rules = $settings->rulesForProfile($profile);

        return ($from ?? CarbonImmutable::now())->addDays($rules['loan_days']);
    }
}
