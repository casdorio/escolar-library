<?php

declare(strict_types=1);

namespace Escolar\Library\Support;

use Carbon\CarbonImmutable;
use Escolar\Library\DTOs\ClassroomHoldResult;
use Escolar\Library\Enums\ClassroomHoldStatus;
use Escolar\Library\Enums\CopyStatus;
use Escolar\Library\Models\ClassroomHold;
use Escolar\Library\Models\Copy;
use Escolar\Library\Models\Reader;
use Escolar\Library\Models\Title;
use Illuminate\Support\Facades\DB;

/**
 * "Reservar material para aula" — bloqueia exemplares HOJE disponíveis para
 * uma data futura, transicionando cada um para `CopyStatus::Reserved` via
 * {@see CopyLedger} (mesmo status usado pela fila de {@see Reservation}; o
 * que distingue os dois fluxos é o `reference` gravado no evento). Copy
 * "reservado para aula" já não aparece disponível em nenhuma tela — reusa
 * `Title::scopeWithAvailability`, nenhuma tela precisa saber deste módulo.
 */
class ClassroomHoldService
{
    private const MAX_COPIES_PER_HOLD = 10;

    private const MAX_DAYS_AHEAD = 90;

    public function hold(
        Reader $reader,
        Title $title,
        CarbonImmutable $classDate,
        int $copiesRequested,
        ?string $notes,
        CopyLedger $ledger,
    ): ClassroomHoldResult {
        if ($classDate->startOfDay()->lt(CarbonImmutable::now()->startOfDay())) {
            return ClassroomHoldResult::deny('A data da aula não pode ser no passado.');
        }

        if ($classDate->gt(CarbonImmutable::now()->addDays(self::MAX_DAYS_AHEAD))) {
            return ClassroomHoldResult::deny('Reserve com no máximo '.self::MAX_DAYS_AHEAD.' dias de antecedência.');
        }

        if ($copiesRequested < 1 || $copiesRequested > self::MAX_COPIES_PER_HOLD) {
            return ClassroomHoldResult::deny('Escolha entre 1 e '.self::MAX_COPIES_PER_HOLD.' exemplares.');
        }

        return DB::transaction(function () use ($reader, $title, $classDate, $copiesRequested, $notes, $ledger): ClassroomHoldResult {
            $copies = Copy::where('school_id', $title->school_id)
                ->where('title_id', $title->id)
                ->available()
                ->lockForUpdate()
                ->limit($copiesRequested)
                ->get();

            if ($copies->isEmpty()) {
                return ClassroomHoldResult::deny('Nenhum exemplar disponível para reservar.');
            }

            $hold = ClassroomHold::create([
                'school_id' => $title->school_id,
                'title_id' => $title->id,
                'reader_id' => $reader->id,
                'class_date' => $classDate->toDateString(),
                'copies_requested' => $copiesRequested,
                'copies_held' => $copies->count(),
                'status' => ClassroomHoldStatus::Active->value,
                'notes' => $notes,
            ]);

            foreach ($copies as $copy) {
                $ledger->transition($copy, 'classroom_hold', CopyStatus::Reserved->value, null, $hold);
            }

            $hold->copies()->sync($copies->pluck('id'));

            $reason = $copies->count() < $copiesRequested
                ? "Apenas {$copies->count()} de {$copiesRequested} exemplares estavam disponíveis — reservados os que havia."
                : null;

            return ClassroomHoldResult::allow($hold, $reason);
        });
    }

    /**
     * Libera os exemplares de volta para `available` — usada tanto pelo
     * cancelamento do professor quanto pela expiração automática. Só reverte
     * exemplar que ainda está `reserved` (defesa: se alguém já mudou o
     * status por outro caminho, não pisa em cima).
     */
    public function release(ClassroomHold $hold, CopyLedger $ledger, string $event, string $finalStatus): void
    {
        DB::transaction(function () use ($hold, $ledger, $event, $finalStatus): void {
            $copies = $hold->copies()->lockForUpdate()->get();

            foreach ($copies as $copy) {
                if ($copy->status?->value === CopyStatus::Reserved->value) {
                    $ledger->transition($copy, $event, CopyStatus::Available->value, null, $hold);
                }
            }

            $hold->update(['status' => $finalStatus, 'released_at' => now()]);
        });
    }

    /**
     * Libera sozinho os holds cuja data de aula já passou — chamada de
     * forma preguiçosa nos endpoints de listagem/criação (sem depender do
     * scheduler, que é desligado por padrão por escola neste sistema).
     */
    public function releaseExpired(int $schoolId, CopyLedger $ledger): int
    {
        $expired = ClassroomHold::where('school_id', $schoolId)
            ->active()
            ->where('class_date', '<', CarbonImmutable::now()->startOfDay()->toDateString())
            ->get();

        foreach ($expired as $hold) {
            $this->release($hold, $ledger, 'classroom_hold_expired', ClassroomHoldStatus::Released->value);
        }

        return $expired->count();
    }
}
