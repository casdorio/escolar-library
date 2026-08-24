<?php

declare(strict_types=1);

namespace Escolar\Library\Support;

use Escolar\Library\DTOs\DigitalLoanResult;
use Escolar\Library\Models\DigitalItem;
use Escolar\Library\Models\DigitalLoan;
use Escolar\Library\Models\LibrarySettings;
use Escolar\Library\Models\Reader;

/**
 * Biblioteca Virtual (Fase 7.1) — empréstimo digital, compartilhado entre
 * APP e os dois portais externos (RESPONSAVEL-ALUNO e PROFESSOR-portal),
 * já que é lá que o leitor de verdade lê o livro. Nunca gera multa: vence e
 * "se devolve" sozinho (ver {@see expireDue}).
 */
class DigitalLoanService
{
    public function issue(DigitalItem $item, Reader $reader): DigitalLoanResult
    {
        if (! $item->is_active) {
            return DigitalLoanResult::deny('Esta edição digital não está ativa.');
        }

        $existing = DigitalLoan::query()
            ->where('digital_item_id', $item->id)
            ->where('reader_id', $reader->id)
            ->where('status', 'active')
            ->first();

        if ($existing) {
            return DigitalLoanResult::allow($existing);
        }

        if (! $item->hasLicenseAvailable()) {
            return DigitalLoanResult::deny('Todas as licenças simultâneas deste título digital estão em uso agora.');
        }

        $settings = LibrarySettings::forSchool($item->school_id);
        $accessProfiles = $settings->digital_access_profiles ?? [];
        $readerProfile = $reader->profile instanceof \BackedEnum ? $reader->profile->value : (string) $reader->profile;
        if (! empty($accessProfiles) && ! in_array($readerProfile, $accessProfiles, true)) {
            return DigitalLoanResult::deny('O perfil deste leitor não tem acesso à Biblioteca Virtual nesta escola.');
        }

        $loanDays = $settings->digital_loan_days ?: 14;

        $loan = DigitalLoan::create([
            'school_id' => $item->school_id,
            'digital_item_id' => $item->id,
            'reader_id' => $reader->id,
            'started_at' => now(),
            'expires_at' => now()->addDays($loanDays),
            'status' => 'active',
        ]);

        return DigitalLoanResult::allow($loan);
    }

    /**
     * Chamada preguiçosamente (sem job agendado por escola) no início de
     * qualquer leitura/listagem de empréstimo digital da escola.
     */
    public function expireDue(int $schoolId): int
    {
        return DigitalLoan::query()
            ->where('school_id', $schoolId)
            ->where('status', 'active')
            ->where('expires_at', '<', now())
            ->update([
                'status' => 'expired',
                'returned_at' => now(),
                'auto_returned' => true,
            ]);
    }

    public function recordProgress(DigitalLoan $loan, int $percent, ?string $location, int $secondsReadDelta): void
    {
        $progress = $loan->progress()->firstOrNew([]);
        $progress->school_id = $loan->school_id;
        $progress->digital_loan_id = $loan->id;
        $progress->reader_id = $loan->reader_id;
        $progress->percent = max($progress->percent ?? 0, min(100, max(0, $percent)));
        $progress->location = $location ?? $progress->location;
        $progress->seconds_read = ($progress->seconds_read ?? 0) + max(0, $secondsReadDelta);
        $progress->last_read_at = now();
        $progress->save();
    }
}
