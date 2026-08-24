<?php

declare(strict_types=1);

namespace Escolar\Library\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

/**
 * Empréstimo digital — nunca gera multa, expira e "se devolve" sozinho
 * (Fase 7.1). Ver {@see \Escolar\Library\Models\DigitalItem}.
 */
class DigitalLoan extends Model
{
    use HasUlids;

    protected $table = 'library_digital_loans';

    protected $fillable = [
        'school_id', 'digital_item_id', 'reader_id', 'started_at', 'expires_at',
        'returned_at', 'status', 'auto_returned',
    ];

    protected $casts = [
        'started_at' => 'datetime',
        'expires_at' => 'datetime',
        'returned_at' => 'datetime',
        'auto_returned' => 'boolean',
    ];

    /** @return array<int, string> */
    public function uniqueIds(): array
    {
        return ['public_id'];
    }

    public function digitalItem(): BelongsTo
    {
        return $this->belongsTo(DigitalItem::class);
    }

    public function reader(): BelongsTo
    {
        return $this->belongsTo(Reader::class);
    }

    public function progress(): HasOne
    {
        return $this->hasOne(ReadingProgress::class);
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('status', 'active');
    }
}
