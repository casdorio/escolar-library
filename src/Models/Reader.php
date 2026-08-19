<?php

declare(strict_types=1);

namespace Escolar\Library\Models;

use Escolar\Library\Enums\ReaderProfile;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class Reader extends Model
{
    use HasUlids;

    protected $table = 'library_readers';

    protected $fillable = [
        'school_id', 'readerable_type', 'readerable_id', 'profile', 'name',
        'document', 'status', 'suspended_until', 'notes', 'joined_at',
    ];

    protected $casts = [
        'profile' => ReaderProfile::class,
        'suspended_until' => 'datetime',
        'joined_at' => 'datetime',
    ];

    /** @return array<int, string> */
    public function uniqueIds(): array
    {
        return ['public_id'];
    }

    public function readerable(): MorphTo
    {
        return $this->morphTo();
    }

    public function loans(): HasMany
    {
        return $this->hasMany(Loan::class);
    }

    public function isSuspended(): bool
    {
        if ($this->status === 'suspended') {
            return $this->suspended_until === null || $this->suspended_until->isFuture();
        }

        return false;
    }

    public function scopeSearch(Builder $query, ?string $term): Builder
    {
        if (! $term) {
            return $query;
        }

        return $query->where(function (Builder $q) use ($term): void {
            $q->where('name', 'ilike', "%{$term}%")
                ->orWhere('document', 'ilike', "%{$term}%");
        });
    }
}
