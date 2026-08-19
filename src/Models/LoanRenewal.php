<?php

declare(strict_types=1);

namespace Escolar\Library\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LoanRenewal extends Model
{
    protected $table = 'library_loan_renewals';

    protected $fillable = [
        'school_id', 'loan_id', 'previous_due_at', 'new_due_at', 'renewed_by', 'channel',
    ];

    protected $casts = [
        'previous_due_at' => 'datetime',
        'new_due_at' => 'datetime',
    ];

    public function loan(): BelongsTo
    {
        return $this->belongsTo(Loan::class);
    }
}
