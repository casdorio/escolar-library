<?php

declare(strict_types=1);

namespace Escolar\Library\Support;

use Carbon\CarbonImmutable;
use Escolar\Library\Enums\OverduePolicy;
use Escolar\Library\Models\Fine;
use Escolar\Library\Models\LibrarySettings;
use Escolar\Library\Models\Loan;

/**
 * Aplica a política de atraso da escola na devolução — os quatro modos, num
 * lugar só (§2.1-K do plano). Nunca decide política; só executa a que a
 * escola escolheu.
 */
class OverduePolicyResolver
{
    public function apply(Loan $loan): void
    {
        if ($loan->returned_at === null || $loan->due_at->isFuture()) {
            return;
        }

        $daysLate = (int) CarbonImmutable::parse($loan->due_at)->diffInDays(CarbonImmutable::parse($loan->returned_at));
        if ($daysLate <= 0) {
            return;
        }

        $settings = LibrarySettings::forSchool($loan->school_id);
        $policy = $settings->overdue_policy ?? OverduePolicy::None;
        if ($policy === OverduePolicy::None) {
            return;
        }

        $reader = $loan->reader;
        $rules = $settings->rulesForProfile($reader->profile?->value ?? 'external');

        if ($policy->hasFine() && $daysLate > $rules['tolerance_days']) {
            $chargeableDays = $daysLate - $rules['tolerance_days'];
            $amount = min($chargeableDays * $rules['fine_per_day_cents'], $rules['fine_cap_cents'] ?: PHP_INT_MAX);

            if ($amount > 0) {
                Fine::create([
                    'school_id' => $loan->school_id,
                    'loan_id' => $loan->id,
                    'reader_id' => $reader->id,
                    'days_late' => $daysLate,
                    'amount_cents' => $amount,
                    'status' => 'pending',
                ]);
            }
        }

        if ($policy->hasSuspension() && $rules['suspension_days_per_day_late'] > 0) {
            $suspendDays = $daysLate * $rules['suspension_days_per_day_late'];
            $until = CarbonImmutable::now()->addDays($suspendDays);

            // Não encurta uma suspensão já mais longa (ex.: outro atraso pendente).
            if (! $reader->suspended_until || $reader->suspended_until->lt($until)) {
                $reader->update(['status' => 'suspended', 'suspended_until' => $until]);
            }
        }
    }
}
