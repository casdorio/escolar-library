<?php

declare(strict_types=1);

namespace Escolar\Library;

use Illuminate\Support\ServiceProvider;

/**
 * Núcleo compartilhado da Biblioteca (pacote `escolar/library`).
 *
 * Hoje só registra o pacote no container Laravel — não há config nem binding
 * ainda porque os models/contratos do acervo nascem na Fase 1 (ver
 * _DOCUMENTACAO/biblioteca/PLANO-BIBLIOTECA.md, Parte 3 e 6). Segue o mesmo
 * papel de `Escolar\Ai\AiCoreServiceProvider`: um lugar central onde o
 * binding de contratos (ex.: LibraryCatalogReader) é registrado quando as
 * fases seguintes precisarem, para os três apps consumidores (APP,
 * PROFESSOR-portal, RESPONSAVEL-ALUNO) resolverem a mesma implementação.
 */
class LibraryCoreServiceProvider extends ServiceProvider
{
    public function register(): void {}
}
