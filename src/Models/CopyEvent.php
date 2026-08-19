<?php

declare(strict_types=1);

namespace Escolar\Library\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

/**
 * Ledger imutável de eventos do exemplar — nunca editado, só inserido.
 * Ver {@see \Escolar\Library\Support\CopyLedger}.
 */
class CopyEvent extends Model
{
    public $timestamps = false;

    protected $table = 'library_copy_events';

    protected $fillable = [
        'school_id', 'copy_id', 'event', 'from_status', 'to_status',
        'reference_type', 'reference_id', 'actor_id', 'created_at',
    ];

    protected $casts = [
        'created_at' => 'datetime',
    ];

    public function copy(): BelongsTo
    {
        return $this->belongsTo(Copy::class);
    }

    public function reference(): MorphTo
    {
        return $this->morphTo();
    }
}
