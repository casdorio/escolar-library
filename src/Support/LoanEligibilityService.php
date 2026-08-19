<?php

declare(strict_types=1);

namespace Escolar\Library\Support;

use Escolar\Library\DTOs\EligibilityResult;
use Escolar\Library\Enums\CopyStatus;
use Escolar\Library\Models\Copy;
use Escolar\Library\Models\LibrarySettings;
use Escolar\Library\Models\Reader;

/**
 * "Pode emprestar?" — resultado tipado com o motivo, consultado no balcão e
 * (Fase 5) no portal. Nenhum dos dois recalcula: ambos chamam este serviço.
 */
class LoanEligibilityService
{
    public function check(Reader $reader, Copy $copy): EligibilityResult
    {
        if ($reader->status === 'blocked') {
            return EligibilityResult::deny('Leitor bloqueado.');
        }

        if ($reader->status === 'inactive') {
            return EligibilityResult::deny('Leitor inativo.');
        }

        if ($reader->isSuspended()) {
            $until = $reader->suspended_until?->format('d/m/Y');

            return EligibilityResult::deny($until ? "Leitor suspenso até {$until}." : 'Leitor suspenso.');
        }

        if ($copy->status?->value !== CopyStatus::Available->value) {
            return EligibilityResult::deny('Este exemplar não está disponível para empréstimo.');
        }

        $settings = LibrarySettings::forSchool($reader->school_id);
        $rules = $settings->rulesForProfile($reader->profile?->value ?? 'external');

        $openLoans = $reader->loans()->open()->count();
        if ($openLoans >= $rules['max_items']) {
            return EligibilityResult::deny("Limite de {$rules['max_items']} itens simultâneos atingido.");
        }

        return EligibilityResult::allow();
    }
}
