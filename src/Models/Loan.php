<?php

declare(strict_types=1);

namespace Escolar\Library\Models;

use Escolar\Library\Enums\LoanStatus;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Loan extends Model
{
    use HasUlids;

    protected $table = 'library_loans';

    protected $fillable = [
        'school_id', 'copy_id', 'reader_id', 'loaned_at', 'due_at', 'returned_at',
        'renewals_count', 'status', 'loaned_by', 'returned_by', 'notes', 'idempotency_key',
    ];

    protected $casts = [
        'loaned_at' => 'datetime',
        'due_at' => 'datetime',
        'returned_at' => 'datetime',
        'renewals_count' => 'integer',
        'status' => LoanStatus::class,
    ];

    /** @return array<int, string> */
    public function uniqueIds(): array
    {
        return ['public_id'];
    }

    public function copy(): BelongsTo
    {
        return $this->belongsTo(Copy::class);
    }

    public function reader(): BelongsTo
    {
        return $this->belongsTo(Reader::class);
    }

    public function renewals(): HasMany
    {
        return $this->hasMany(LoanRenewal::class);
    }

    public function scopeOpen(Builder $query): Builder
    {
        return $query->whereIn('status', [LoanStatus::Open->value, LoanStatus::Late->value]);
    }

    public function isOverdue(): bool
    {
        return $this->returned_at === null && $this->due_at->isPast();
    }
}
