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

    public static function forSchool(int $schoolId): self
    {
        return self::firstOrCreate(['school_id' => $schoolId]);
    }
}
