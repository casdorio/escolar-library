<?php

declare(strict_types=1);

namespace Escolar\Library\Models;

use Escolar\Library\Enums\AudienceMode;
use Escolar\Library\Enums\OverduePolicy;
use Illuminate\Database\Eloquent\Model;

class LibrarySettings extends Model
{
    protected $table = 'library_settings';

    protected $fillable = [
        'school_id', 'overdue_policy', 'fines_go_to_finance', 'digital_access_profiles',
        'digital_loan_days', 'digital_concurrent_limit',
        'audience_enforcement_mode', 'tombo_prefix', 'next_tombo_sequence',
        'classification_system', 'auto_create_reader_on_enrollment',
        'profile_rules', 'use_school_calendar', 'reservations_enabled',
        'reservations_by_reader', 'reservation_hold_hours', 'max_reservations_per_reader',
        'block_renewal_when_queued',
    ];

    protected $casts = [
        'overdue_policy' => OverduePolicy::class,
        'fines_go_to_finance' => 'boolean',
        'digital_access_profiles' => 'array',
        'digital_loan_days' => 'integer',
        'digital_concurrent_limit' => 'integer',
        'audience_enforcement_mode' => AudienceMode::class,
        'auto_create_reader_on_enrollment' => 'boolean',
        'profile_rules' => 'array',
        'use_school_calendar' => 'boolean',
        'reservations_enabled' => 'boolean',
        'reservations_by_reader' => 'boolean',
        'block_renewal_when_queued' => 'boolean',
    ];

    /**
     * Regras padrão por perfil quando a escola ainda não configurou nada
     * (Fase 3 traz a tela de Configurações). loan_days/max_items/max_renewals
     * pela Fase 2; fine_per_day_cents/tolerance_days/fine_cap_cents/
     * suspension_days_per_day_late pela Fase 3 (só usados conforme
     * overdue_policy da escola).
     */
    public const DEFAULT_PROFILE_RULES = [
        'student' => [
            'loan_days' => 14, 'max_items' => 3, 'max_renewals' => 1,
            'fine_per_day_cents' => 50, 'tolerance_days' => 1, 'fine_cap_cents' => 3000,
            'suspension_days_per_day_late' => 1,
        ],
        'teacher' => [
            'loan_days' => 30, 'max_items' => 5, 'max_renewals' => 2,
            'fine_per_day_cents' => 0, 'tolerance_days' => 3, 'fine_cap_cents' => 0,
            'suspension_days_per_day_late' => 0,
        ],
        'staff' => [
            'loan_days' => 14, 'max_items' => 3, 'max_renewals' => 1,
            'fine_per_day_cents' => 0, 'tolerance_days' => 2, 'fine_cap_cents' => 0,
            'suspension_days_per_day_late' => 1,
        ],
        'guardian' => [
            'loan_days' => 14, 'max_items' => 2, 'max_renewals' => 1,
            'fine_per_day_cents' => 50, 'tolerance_days' => 1, 'fine_cap_cents' => 3000,
            'suspension_days_per_day_late' => 1,
        ],
        'external' => [
            'loan_days' => 7, 'max_items' => 1, 'max_renewals' => 0,
            'fine_per_day_cents' => 100, 'tolerance_days' => 0, 'fine_cap_cents' => 2000,
            'suspension_days_per_day_late' => 2,
        ],
    ];

    /**
     * @return array{loan_days: int, max_items: int, max_renewals: int, fine_per_day_cents: int, tolerance_days: int, fine_cap_cents: int, suspension_days_per_day_late: int}
     */
    public function rulesForProfile(string $profile): array
    {
        $configured = $this->profile_rules[$profile] ?? null;
        $default = self::DEFAULT_PROFILE_RULES[$profile] ?? self::DEFAULT_PROFILE_RULES['external'];

        return array_merge($default, $configured ?? []);
    }

    /**
     * `firstOrCreate` só grava `school_id` na 1ª visita — o resto vem dos
     * defaults da coluna no banco, que o Eloquent NÃO relê pro objeto em
     * memória depois do INSERT (isso deixava `overdue_policy` e outros
     * campos `null` na tela de Configurações até a escola salvar 1x).
     * Passamos os defaults explícitos aqui pra o objeto retornado já vir
     * correto, sem depender de um SELECT extra.
     */
    public static function forSchool(int $schoolId): self
    {
        return self::firstOrCreate(
            ['school_id' => $schoolId],
            [
                'overdue_policy' => OverduePolicy::Suspension->value,
                'fines_go_to_finance' => false,
                'digital_loan_days' => 14,
                'audience_enforcement_mode' => AudienceMode::Advisory->value,
                'tombo_prefix' => '',
                'next_tombo_sequence' => 1,
                'classification_system' => 'cdd',
                'use_school_calendar' => false,
                'reservations_enabled' => true,
                'reservations_by_reader' => false,
                'reservation_hold_hours' => 48,
                'max_reservations_per_reader' => 2,
                'block_renewal_when_queued' => true,
                'auto_create_reader_on_enrollment' => true,
            ],
        );
    }
}
