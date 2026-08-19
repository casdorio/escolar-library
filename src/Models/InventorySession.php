<?php

declare(strict_types=1);

namespace Escolar\Library\Models;

use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/** Nome de classe evita colisão com o `Inventory` genérico de outros módulos. */
class InventorySession extends Model
{
    use HasUlids;

    protected $table = 'library_inventories';

    protected $fillable = ['school_id', 'name', 'started_at', 'finished_at', 'status', 'scope', 'started_by'];

    protected $casts = [
        'started_at' => 'datetime',
        'finished_at' => 'datetime',
        'scope' => 'array',
    ];

    /** @return array<int, string> */
    public function uniqueIds(): array
    {
        return ['public_id'];
    }

    public function items(): HasMany
    {
        return $this->hasMany(InventoryItem::class, 'inventory_id');
    }
}
