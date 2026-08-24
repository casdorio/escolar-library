<?php

declare(strict_types=1);

namespace Escolar\Library\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Vetor de embedding de um título — Busca por assunto (Fase 7.7).
 *
 * `vector` guarda em JSON (Plano B da decisão de infra — sem pgvector em
 * produção ainda). `source_hash` evita reindexar título sem mudança real.
 */
class TitleEmbedding extends Model
{
    protected $table = 'library_title_embeddings';

    protected $fillable = [
        'school_id', 'title_id', 'vector', 'model', 'source_hash', 'indexed_at',
    ];

    protected $casts = [
        'vector' => 'array',
        'indexed_at' => 'datetime',
    ];

    public function title(): BelongsTo
    {
        return $this->belongsTo(Title::class);
    }
}
