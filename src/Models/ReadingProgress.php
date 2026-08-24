<?php

declare(strict_types=1);

namespace Escolar\Library\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ReadingProgress extends Model
{
    protected $table = 'library_reading_progress';

    protected $fillable = [
        'school_id', 'digital_loan_id', 'reader_id', 'percent', 'location',
        'seconds_read', 'last_read_at',
    ];

    protected $casts = [
        'percent' => 'integer',
        'seconds_read' => 'integer',
        'last_read_at' => 'datetime',
    ];

    public function digitalLoan(): BelongsTo
    {
        return $this->belongsTo(DigitalLoan::class);
    }

    public function reader(): BelongsTo
    {
        return $this->belongsTo(Reader::class);
    }
}
