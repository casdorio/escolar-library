<?php

declare(strict_types=1);

namespace Escolar\Library\Models;

use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Author extends Model
{
    use HasUlids;

    protected $table = 'library_authors';

    protected $fillable = [
        'school_id', 'name', 'normalized_name', 'birth_year', 'death_year', 'bio', 'nationality',
    ];

    protected $casts = [
        'birth_year' => 'integer',
        'death_year' => 'integer',
    ];

    protected static function booted(): void
    {
        static::saving(function (self $author): void {
            $author->normalized_name = self::normalize($author->name);
        });
    }

    public static function normalize(string $name): string
    {
        $normalized = str($name)->lower()->ascii()->trim()->toString();

        return $normalized;
    }

    /** @return array<int, string> */
    public function uniqueIds(): array
    {
        return ['public_id'];
    }

    public function titles(): BelongsToMany
    {
        return $this->belongsToMany(Title::class, 'library_title_authors', 'author_id', 'title_id')
            ->withPivot(['role', 'sort_order']);
    }
}
