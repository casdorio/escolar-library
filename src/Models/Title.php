<?php

declare(strict_types=1);

namespace Escolar\Library\Models;

use Escolar\Library\Enums\MaterialType;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Uma obra do acervo (título). Pode ter N exemplares físicos ({@see Copy}) e,
 * com a capability library.virtual, um item digital (Fase 7.1).
 */
class Title extends Model
{
    use HasUlids;

    protected $table = 'library_titles';

    protected $fillable = [
        'school_id', 'title', 'subtitle', 'isbn', 'issn', 'publisher_id', 'edition',
        'published_year', 'language', 'pages', 'synopsis', 'cover_path',
        'classification_code', 'cutter_code', 'category_id', 'material_type',
        'age_range', 'tags', 'audience_scope', 'is_active',
    ];

    protected $casts = [
        'published_year' => 'integer',
        'pages' => 'integer',
        'tags' => 'array',
        'is_active' => 'boolean',
        'material_type' => MaterialType::class,
    ];

    /** @return array<int, string> */
    public function uniqueIds(): array
    {
        return ['public_id'];
    }

    public function publisher(): BelongsTo
    {
        return $this->belongsTo(Publisher::class);
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    public function authors(): BelongsToMany
    {
        return $this->belongsToMany(Author::class, 'library_title_authors', 'title_id', 'author_id')
            ->withPivot(['role', 'sort_order'])
            ->orderByPivot('sort_order');
    }

    public function copies(): HasMany
    {
        return $this->hasMany(Copy::class);
    }

    public function audiences(): HasMany
    {
        return $this->hasMany(TitleAudience::class);
    }

    /**
     * Anota available_copies_count/total_copies_count sem N+1 — evita cada
     * tela do acervo montar sua própria query de disponibilidade (§0.6 do plano).
     */
    public function scopeWithAvailability(Builder $query): Builder
    {
        return $query
            ->withCount('copies as total_copies_count')
            ->withCount(['copies as available_copies_count' => function (Builder $q): void {
                $q->where('status', 'available');
            }]);
    }

    public function scopeSearch(Builder $query, ?string $term): Builder
    {
        if (! $term) {
            return $query;
        }

        return $query->where(function (Builder $q) use ($term): void {
            $q->where('title', 'ilike', "%{$term}%")
                ->orWhere('isbn', 'ilike', "%{$term}%")
                ->orWhereHas('authors', function (Builder $a) use ($term): void {
                    $a->where('name', 'ilike', "%{$term}%");
                });
        });
    }
}
