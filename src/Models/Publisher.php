<?php

declare(strict_types=1);

namespace Escolar\Library\Models;

use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Publisher extends Model
{
    use HasUlids;

    protected $table = 'library_publishers';

    protected $fillable = ['school_id', 'name', 'normalized_name', 'city'];

    protected static function booted(): void
    {
        static::saving(function (self $publisher): void {
            $publisher->normalized_name = str($publisher->name)->lower()->ascii()->trim()->toString();
        });
    }

    /** @return array<int, string> */
    public function uniqueIds(): array
    {
        return ['public_id'];
    }

    public function titles(): HasMany
    {
        return $this->hasMany(Title::class, 'publisher_id');
    }
}
