<?php

declare(strict_types=1);

namespace Escolar\Library\Support;

use Escolar\Library\Models\Loan;
use Escolar\Library\Models\Reader;

/**
 * "Leitura Gamificada" (Fase 7.2 do plano da Biblioteca) — nível, sequência
 * de devoluções no prazo e conquistas, calculados só a partir do histórico
 * de empréstimos já existente ({@see Loan}). Deliberadamente sem tabela
 * nova: tudo aqui é derivado, então nunca fica desatualizado e não precisa
 * de migration nem de sincronismo com nada.
 */
class ReaderGamificationService
{
    /**
     * @return list<array{index: int, threshold: int, name: string, icon: string}>
     */
    public static function levels(): array
    {
        return [
            ['index' => 1, 'threshold' => 0, 'name' => 'Explorador Iniciante', 'icon' => '🌱'],
            ['index' => 2, 'threshold' => 3, 'name' => 'Aventureiro das Páginas', 'icon' => '🧭'],
            ['index' => 3, 'threshold' => 8, 'name' => 'Caçador de Histórias', 'icon' => '🔎'],
            ['index' => 4, 'threshold' => 15, 'name' => 'Mestre das Letras', 'icon' => '🪄'],
            ['index' => 5, 'threshold' => 30, 'name' => 'Lenda da Biblioteca', 'icon' => '🏆'],
        ];
    }

    /**
     * @return list<array{key: string, name: string, icon: string, description: string}>
     */
    private static function badgeCatalog(): array
    {
        return [
            ['key' => 'first_book', 'name' => 'Primeira Página', 'icon' => '🌟', 'description' => 'Terminou o primeiro livro emprestado.'],
            ['key' => 'books_5', 'name' => 'Leitor Iniciante', 'icon' => '📖', 'description' => 'Leu 5 livros.'],
            ['key' => 'books_15', 'name' => 'Leitor Voraz', 'icon' => '🔥', 'description' => 'Leu 15 livros.'],
            ['key' => 'books_30', 'name' => 'Mestre dos Livros', 'icon' => '👑', 'description' => 'Leu 30 livros.'],
            ['key' => 'books_50', 'name' => 'Lenda da Biblioteca', 'icon' => '🏆', 'description' => 'Leu 50 livros.'],
            ['key' => 'streak_5', 'name' => 'Pontual', 'icon' => '⏰', 'description' => '5 devoluções seguidas no prazo.'],
            ['key' => 'streak_10', 'name' => 'Sequência de Ouro', 'icon' => '⭐', 'description' => '10 devoluções seguidas no prazo.'],
            ['key' => 'genres_5', 'name' => 'Explorador de Gêneros', 'icon' => '🧭', 'description' => 'Leu obras de 5 categorias diferentes.'],
            ['key' => 'marathon', 'name' => 'Maratonista', 'icon' => '🚀', 'description' => 'Leu 3 livros no mesmo mês.'],
        ];
    }

    /**
     * @return array{
     *     books_read: int,
     *     current_streak: int,
     *     best_streak: int,
     *     level: array{index: int, name: string, icon: string},
     *     next_level: ?array{name: string, icon: string, books_needed: int},
     *     progress_percent: int,
     *     badges: list<array{key: string, name: string, icon: string, description: string, unlocked: bool}>,
     *     unlocked_count: int,
     *     total_badges: int,
     * }
     */
    public function statsFor(Reader $reader): array
    {
        $loans = Loan::where('reader_id', $reader->id)
            ->whereNotNull('returned_at')
            ->with('copy.title:id,category_id')
            ->orderBy('returned_at')
            ->get();

        $booksRead = $loans->count();

        $onTimeFlags = $loans->map(fn (Loan $l) => $l->returned_at->lte($l->due_at))->values();

        $bestStreak = 0;
        $running = 0;
        foreach ($onTimeFlags as $onTime) {
            $running = $onTime ? $running + 1 : 0;
            $bestStreak = max($bestStreak, $running);
        }

        $currentStreak = 0;
        foreach ($onTimeFlags->reverse() as $onTime) {
            if (! $onTime) {
                break;
            }
            $currentStreak++;
        }

        $genreCount = $loans->pluck('copy.title.category_id')->filter()->unique()->count();

        $maxPerMonth = $loans->groupBy(fn (Loan $l) => $l->returned_at->format('Y-m'))
            ->map->count()
            ->max() ?? 0;

        $levels = self::levels();
        $level = $levels[0];
        $nextLevel = null;
        foreach ($levels as $i => $lvl) {
            if ($booksRead >= $lvl['threshold']) {
                $level = $lvl;
                $nextLevel = $levels[$i + 1] ?? null;
            }
        }

        $progressPercent = 100;
        if ($nextLevel !== null) {
            $span = $nextLevel['threshold'] - $level['threshold'];
            $progressPercent = $span > 0
                ? (int) round((($booksRead - $level['threshold']) / $span) * 100)
                : 0;
        }

        $unlockedKeys = array_filter([
            $booksRead >= 1 ? 'first_book' : null,
            $booksRead >= 5 ? 'books_5' : null,
            $booksRead >= 15 ? 'books_15' : null,
            $booksRead >= 30 ? 'books_30' : null,
            $booksRead >= 50 ? 'books_50' : null,
            $bestStreak >= 5 ? 'streak_5' : null,
            $bestStreak >= 10 ? 'streak_10' : null,
            $genreCount >= 5 ? 'genres_5' : null,
            $maxPerMonth >= 3 ? 'marathon' : null,
        ]);

        $badges = array_map(
            fn (array $b) => [...$b, 'unlocked' => in_array($b['key'], $unlockedKeys, true)],
            self::badgeCatalog(),
        );

        return [
            'books_read' => $booksRead,
            'current_streak' => $currentStreak,
            'best_streak' => $bestStreak,
            'level' => ['index' => $level['index'], 'name' => $level['name'], 'icon' => $level['icon']],
            'next_level' => $nextLevel === null ? null : [
                'name' => $nextLevel['name'],
                'icon' => $nextLevel['icon'],
                'books_needed' => $nextLevel['threshold'] - $booksRead,
            ],
            'progress_percent' => max(0, min(100, $progressPercent)),
            'badges' => array_values($badges),
            'unlocked_count' => count($unlockedKeys),
            'total_badges' => count(self::badgeCatalog()),
        ];
    }
}
