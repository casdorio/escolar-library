<?php

declare(strict_types=1);

namespace Escolar\Library\Support;

use Carbon\CarbonImmutable;
use Escolar\Library\DTOs\EligibilityResult;
use Escolar\Library\Enums\CopyStatus;
use Escolar\Library\Enums\ReservationStatus;
use Escolar\Library\Models\Copy;
use Escolar\Library\Models\LibrarySettings;
use Escolar\Library\Models\Reader;
use Escolar\Library\Models\Reservation;
use Escolar\Library\Models\Title;
use Illuminate\Support\Facades\DB;

/**
 * Fila de reservas por TÍTULO (FIFO). {@see fulfillOnReturn} é chamada pela
 * Circulação sempre que um exemplar volta — se houver fila, separa para o
 * primeiro da vez em vez de deixar "disponível" para qualquer um.
 */
class ReservationService
{
    public function reserve(Reader $reader, Title $title): EligibilityResult
    {
        $settings = LibrarySettings::forSchool($title->school_id);

        if (! $settings->reservations_enabled) {
            return EligibilityResult::deny('Reservas estão desligadas nesta escola.');
        }

        $openCount = Reservation::where('school_id', $title->school_id)
            ->where('reader_id', $reader->id)
            ->queued()
            ->count();
        if ($openCount >= $settings->max_reservations_per_reader) {
            return EligibilityResult::deny("Limite de {$settings->max_reservations_per_reader} reservas simultâneas atingido.");
        }

        $alreadyQueued = Reservation::where('school_id', $title->school_id)
            ->where('title_id', $title->id)
            ->where('reader_id', $reader->id)
            ->queued()
            ->exists();
        if ($alreadyQueued) {
            return EligibilityResult::deny('Este leitor já está na fila desta obra.');
        }

        $nextPosition = (int) Reservation::where('school_id', $title->school_id)
            ->where('title_id', $title->id)
            ->queued()
            ->max('queue_position') + 1;

        Reservation::create([
            'school_id' => $title->school_id,
            'title_id' => $title->id,
            'reader_id' => $reader->id,
            'status' => ReservationStatus::Queued->value,
            'queue_position' => $nextPosition,
            'reserved_at' => now(),
        ]);

        return EligibilityResult::allow();
    }

    public function cancel(Reservation $reservation): void
    {
        $reservation->update(['status' => ReservationStatus::Cancelled->value]);
    }

    /**
     * Chamada na devolução. Se houver fila para o título do exemplar, separa
     * o exemplar para o primeiro da fila (copy vira 'reserved', não
     * 'available') e devolve true. Devolve false se não havia fila — o
     * chamador então libera o exemplar normalmente.
     */
    public function fulfillOnReturn(Copy $copy, CopyLedger $ledger): bool
    {
        return DB::transaction(function () use ($copy, $ledger): bool {
            $next = Reservation::where('school_id', $copy->school_id)
                ->where('title_id', $copy->title_id)
                ->queued()
                ->orderBy('queue_position')
                ->lockForUpdate()
                ->first();

            if (! $next) {
                return false;
            }

            $settings = LibrarySettings::forSchool($copy->school_id);
            $holdUntil = CarbonImmutable::now()->addHours($settings->reservation_hold_hours);

            $next->update([
                'status' => ReservationStatus::Available->value,
                'available_copy_id' => $copy->id,
                'available_until' => $holdUntil,
            ]);

            $ledger->transition($copy, 'reserved', CopyStatus::Reserved->value, null, $next);

            return true;
        });
    }

    /**
     * Expira reservas "separadas" cujo prazo de retenção passou — quem não
     * retirou perde a vez. Repassa o exemplar ao próximo da fila (mesma
     * lógica de {@see fulfillOnReturn}); sem próximo, libera o exemplar.
     */
    public function expireStale(int $schoolId, CopyLedger $ledger): int
    {
        $expired = Reservation::where('school_id', $schoolId)
            ->where('status', ReservationStatus::Available->value)
            ->where('available_until', '<', now())
            ->get();

        foreach ($expired as $reservation) {
            DB::transaction(function () use ($reservation, $ledger): void {
                $copy = $reservation->availableCopy()->lockForUpdate()->first();
                $reservation->update(['status' => ReservationStatus::Expired->value]);

                if ($copy && ! $this->fulfillOnReturn($copy, $ledger)) {
                    $ledger->transition($copy, 'returned', CopyStatus::Available->value, null, $reservation);
                }
            });
        }

        return $expired->count();
    }
}
