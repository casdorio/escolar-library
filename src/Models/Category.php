<?php

declare(strict_types=1);

namespace Escolar\Library\Models;

use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Category extends Model
{
    use HasUlids;

    protected $table = 'library_categories';

    public $timestamps = true;

    protected $fillable = [
        'school_id', 'parent_id', 'name', 'code', 'color', 'sort_order',
    ];

    protected $casts = [
        'sort_order' => 'integer',
    ];

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

    public function titles(): HasMany
    {
        return $this->hasMany(Title::class, 'category_id');
    }
}
