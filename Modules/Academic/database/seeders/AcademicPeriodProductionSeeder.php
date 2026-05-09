<?php

namespace Modules\Academic\Database\Seeders;

use Illuminate\Database\Seeder;
use Modules\Academic\Models\AcademicPeriod;
use Modules\Academic\Models\EvaluationParameter;
use Modules\Institucion\Models\Curriculum;

class AcademicPeriodProductionSeeder extends Seeder
{
    public function run(): void
    {
        // ── 3 períodos académicos ─────────────────────────────────────────────
        $periods = [
            [
                'name'       => '2024-I (Enero - Junio 2024)',
                'start_date' => '2024-01-08',
                'end_date'   => '2024-06-28',
                'active'  => false,
                'status'     => 'closed',
            ],
            [
                'name'       => '2024-II (Julio - Diciembre 2024)',
                'start_date' => '2024-07-08',
                'end_date'   => '2024-12-20',
                'active'  => false,
                'status'     => 'closed',
            ],
            [
                'name'       => '2025-I (Enero - Junio 2025)',
                'start_date' => '2025-01-06',
                'end_date'   => '2025-06-27',
                'active'  => true,
                'status'     => 'active',
            ],
        ];

        $periodModels = [];
        foreach ($periods as $data) {
            $periodModels[] = AcademicPeriod::create($data);
        }

        $this->command->info('✓ Períodos académicos creados: 3');

        // ── Parámetros de evaluación globales (aplican a todos) ───────────────
        // Se crean para el período activo (2025-I) con curriculum_id = null
        $activePeriod = $periodModels[2];

        $parameters = [
            ['name' => 'Primer Parcial',    'percentage' => 30.00, 'is_final' => false],
            ['name' => 'Segundo Parcial',   'percentage' => 30.00, 'is_final' => false],
            ['name' => 'Examen Final',      'percentage' => 40.00, 'is_final' => true],
        ];

        // Para los 3 períodos
        foreach ($periodModels as $period) {
            foreach ($parameters as $param) {
                EvaluationParameter::create(array_merge($param, [
                    'academic_period_id' => $period->id,
                    'curriculum_id'      => null,
                    'active'             => true,
                ]));
            }
        }

        $this->command->info('✓ Parámetros de evaluación creados: ' . (3 * count($parameters)));
        $this->command->info('');
        $this->command->info('═══════════════════════════════════════════════');
        $this->command->info('  AcademicPeriodProductionSeeder DONE ✓        ');
        $this->command->info('═══════════════════════════════════════════════');
    }
}