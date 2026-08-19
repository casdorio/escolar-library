<?php

declare(strict_types=1);

namespace Escolar\Library\Models;

use Escolar\Library\Enums\CopyStatus;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Copy extends Model
{
    use HasUlids;

    protected $table = 'library_copies';

    protected $fillable = [
        'school_id', 'title_id', 'tombo', 'barcode', 'status', 'acquisition_type',
        'acquisition_date', 'acquisition_value_cents', 'supplier', 'location_id',
        'condition', 'notes',
    ];

    protected $casts = [
        'acquisition_date' => 'date',
        'acquisition_value_cents' => 'integer',
        'status' => CopyStatus::class,
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

    public function location(): BelongsTo
    {
        return $this->belongsTo(Location::class);
    }

    public function events(): HasMany
    {
        return $this->hasMany(CopyEvent::class);
    }

    public function loans(): HasMany
    {
        return $this->hasMany(Loan::class);
    }

    public function scopeAvailable(Builder $query): Builder
    {
        return $query->where('status', CopyStatus::Available->value);
    }
}
