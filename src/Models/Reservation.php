<?php

declare(strict_types=1);

namespace Escolar\Library\Models;

use Escolar\Library\Enums\ReservationStatus;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Reservation extends Model
{
    use HasUlids;

    protected $table = 'library_reservations';

    protected $fillable = [
        'school_id', 'title_id', 'reader_id', 'status', 'queue_position', 'reserved_at',
        'available_copy_id', 'available_until', 'fulfilled_loan_id',
    ];

    protected $casts = [
        'status' => ReservationStatus::class,
        'queue_position' => 'integer',
        'reserved_at' => 'datetime',
        'available_until' => 'datetime',
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

    public function availableCopy(): BelongsTo
    {
        return $this->belongsTo(Copy::class, 'available_copy_id');
    }

    public function scopeQueued(Builder $query): Builder
    {
        return $query->where('status', ReservationStatus::Queued->value);
    }
}
