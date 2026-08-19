<?php

declare(strict_types=1);

namespace Escolar\Library\Models;

use Escolar\Library\Enums\AudienceType;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TitleAudience extends Model
{
    protected $table = 'library_title_audiences';

    protected $fillable = ['school_id', 'title_id', 'audience_type', 'audience_id', 'profile'];

    protected $casts = [
        'audience_type' => AudienceType::class,
    ];

    public function title(): BelongsTo
    {
        return $this->belongsTo(Title::class);
    }
}
