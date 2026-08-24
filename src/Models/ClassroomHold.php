<?php

declare(strict_types=1);

namespace Escolar\Library\Models;

use Escolar\Library\Enums\ClassroomHoldStatus;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

/**
 * "Reservar material para aula" — professor bloqueia N exemplares HOJE
 * disponíveis para uma data futura, sem passar pela fila de
 * {@see Reservation} (que só reserva no momento em que um exemplar volta).
 */
class ClassroomHold extends Model
{
    use HasUlids;

    protected $table = 'library_classroom_holds';

    protected $fillable = [
        'school_id', 'title_id', 'reader_id', 'class_date', 'copies_requested',
        'copies_held', 'status', 'notes', 'released_at',
    ];

    protected $casts = [
        'class_date' => 'date',
        'copies_requested' => 'integer',
        'copies_held' => 'integer',
        'status' => ClassroomHoldStatus::class,
        'released_at' => 'datetime',
    ];

    /** @return array<int, string> */
    public function uniqueIds(): array
    {
        return ['public_id'];
    }

    public function title(): BelongsTo
    {
        return $this->belongsTo(Title::class);
    }

    public function reader(): BelongsTo
    {
        return $this->belongsTo(Reader::class);
    }

    public function copies(): BelongsToMany
    {
        return $this->belongsToMany(Copy::class, 'library_classroom_hold_copies', 'classroom_hold_id', 'copy_id');
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('status', ClassroomHoldStatus::Active->value);
    }
}
