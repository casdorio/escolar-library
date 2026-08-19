<?php

declare(strict_types=1);

namespace Escolar\Library\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class InventoryItem extends Model
{
    protected $table = 'library_inventory_items';

    protected $fillable = ['school_id', 'inventory_id', 'copy_id', 'expected_status', 'found_at', 'result'];

    protected $casts = [
        'found_at' => 'datetime',
    ];

    public function inventory(): BelongsTo
    {
        return $this->belongsTo(InventorySession::class, 'inventory_id');
    }

    public function copy(): BelongsTo
    {
        return $this->belongsTo(Copy::class);
    }
}
