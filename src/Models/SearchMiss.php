<?php

declare(strict_types=1);

namespace Escolar\Library\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Busca que não achou nada no acervo — insumo da sugestão de compra
 * (Fase 7.4/7.7). Guarda o texto e o perfil/série de forma agregável, sem
 * identificar o leitor.
 */
class SearchMiss extends Model
{
    public $timestamps = false;

    protected $table = 'library_search_misses';

    protected $fillable = [
        'school_id', 'query', 'reader_profile', 'grade', 'results_count', 'created_at',
    ];

    protected $casts = [
        'results_count' => 'integer',
        'created_at' => 'datetime',
    ];
}
