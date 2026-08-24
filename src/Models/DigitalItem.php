<?php

declare(strict_types=1);

namespace Escolar\Library\Models;

use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Edição digital de um {@see Title} — Biblioteca Virtual (Fase 7.1).
 */
class DigitalItem extends Model
{
    use HasUlids;

    protected $table = 'library_digital_items';

    protected $fillable = [
        'school_id', 'title_id', 'format', 'source', 'file_path', 'external_url',
        'file_size', 'license_count', 'allow_download', 'watermark', 'is_active',
    ];

    protected $casts = [
        'file_size' => 'integer',
        'license_count' => 'integer',
        'allow_download' => 'boolean',
        'watermark' => 'boolean',
        'is_active' => 'boolean',
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

    public function loans(): HasMany
    {
        return $this->hasMany(DigitalLoan::class);
    }

    public function activeLoansCount(): int
    {
        return $this->loans()->where('status', 'active')->count();
    }

    public function hasLicenseAvailable(): bool
    {
        return $this->license_count === null || $this->activeLoansCount() < $this->license_count;
    }
}
