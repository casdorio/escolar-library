<?php

declare(strict_types=1);

namespace Escolar\Library\Support;

use Escolar\Library\Models\Copy;
use Escolar\Library\Models\CopyEvent;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

/**
 * Única porta de entrada para mudar o status de um exemplar. Nunca um
 * `update()` solto — sempre em transação, com lock na linha do exemplar, e
 * sempre gravando o evento correspondente (§0.6/§0.7 do plano da Biblioteca).
 * É esse ledger que sustenta o inventário (Fase 6) e a auditoria de circulação.
 */
class CopyLedger
{
    public function transition(
        Copy $copy,
        string $event,
        string $toStatus,
        ?int $actorId = null,
        ?Model $reference = null,
    ): Copy {
        return DB::transaction(function () use ($copy, $event, $toStatus, $actorId, $reference): Copy {
            /** @var Copy $locked */
            $locked = Copy::where('id', $copy->id)->lockForUpdate()->firstOrFail();

            $fromStatus = $locked->status instanceof \BackedEnum ? $locked->status->value : $locked->status;

            $locked->status = $toStatus;
            $locked->save();

            CopyEvent::create([
                'school_id' => $locked->school_id,
                'copy_id' => $locked->id,
                'event' => $event,
                'from_status' => $fromStatus,
                'to_status' => $toStatus,
                'reference_type' => $reference?->getMorphClass(),
                'reference_id' => $reference?->getKey(),
                'actor_id' => $actorId,
                'created_at' => now(),
            ]);

            return $locked->fresh();
        });
    }
}
