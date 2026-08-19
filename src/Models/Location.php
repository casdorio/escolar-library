<?php

declare(strict_types=1);

namespace Escolar\Library\Models;

use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Location extends Model
{
    use HasUlids;

    protected $table = 'library_locations';

    protected $fillable = ['school_id', 'name', 'parent_id', 'code'];

    /** @return array<int, string> */
    public function uniqueIds(): array
    {
        return ['public_id'];
    }

    public function parent(): BelongsTo
    {
        return $this->belongsTo(self::class, 'parent_id');
    }

    public function children(): HasMany
    {
        return $this->hasMany(self::class, 'parent_id');
    }

    public function copies(): HasMany
    {
        return $this->hasMany(Copy::class, 'location_id');
    }
}
