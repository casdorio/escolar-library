<?php

declare(strict_types=1);

namespace Escolar\Library\Models;

use Escolar\Library\Enums\FineStatus;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Fine extends Model
{
    use HasUlids;

    protected $table = 'library_fines';

    protected $fillable = [
        'school_id', 'loan_id', 'reader_id', 'days_late', 'amount_cents', 'status',
        'paid_at', 'waived_by', 'waive_reason', 'bank_charge_id', 'student_payment_schedule_id',
    ];

    protected $casts = [
        'days_late' => 'integer',
        'amount_cents' => 'integer',
        'status' => FineStatus::class,
        'paid_at' => 'datetime',
    ];

    /** @return array<int, string> */
    public function uniqueIds(): array
    {
        return ['public_id'];
    }

    public function loan(): BelongsTo
    {
        return $this->belongsTo(Loan::class);
    }

    public function reader(): BelongsTo
    {
        return $this->belongsTo(Reader::class);
    }

    public function scopePending(Builder $query): Builder
    {
        return $query->where('status', FineStatus::Pending->value);
    }
}
