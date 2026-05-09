<?php

namespace Modules\Academic\Console\Commands;

use Illuminate\Console\Command;
use Modules\Academic\Models\AcademicPeriod;
use Modules\Academic\Services\ClosePeriodService;

class ClosePeriodCommand extends Command
{
    protected $signature   = 'academic:close-period {period_id? : ID del período académico}';
    protected $description = 'Cierra un período académico: bloquea notas, calcula promedios y marca enrollments como completados.';

    public function handle(ClosePeriodService $service): int
    {
        $this->info('');
        $this->info('╔══════════════════════════════════════════╗');
        $this->info('║     CIERRE DE PERÍODO ACADÉMICO          ║');
        $this->info('╚══════════════════════════════════════════╝');
        $this->info('');

        // Seleccionar período
        $periodId = $this->argument('period_id');

        if (! $periodId) {
            $periods = AcademicPeriod::where('status', '!=', 'closed')
                ->orderByDesc('id')
                ->get(['id', 'name', 'status']);

            if ($periods->isEmpty()) {
                $this->error('No hay períodos disponibles para cerrar.');
                return self::FAILURE;
            }

            $choice = $this->choice(
                '¿Qué período deseas cerrar?',
                $periods->pluck('name', 'id')->toArray()
            );

            $period = $periods->firstWhere('name', $choice);
        } else {
            $period = AcademicPeriod::find($periodId);
        }

        if (! $period) {
            $this->error('Período no encontrado.');
            return self::FAILURE;
        }

        // Mostrar resumen antes de confirmar
        $this->table(
            ['Campo', 'Valor'],
            [
                ['Período',      $period->name],
                ['Estado actual',$period->status],
                ['Fecha inicio', $period->start_date->format('d/m/Y')],
                ['Fecha fin',    $period->end_date->format('d/m/Y')],
            ]
        );

        // Contar datos que se van a procesar
        $enrollments = \Modules\Academic\Models\Enrollment::where('academic_period_id', $period->id)->count();
        $items       = \Modules\Academic\Models\EnrollmentItem::whereHas('enrollment', fn ($q) =>
            $q->where('academic_period_id', $period->id))->count();
        $grades      = \Modules\Academic\Models\Grade::whereHas('enrollmentItem.enrollment', fn ($q) =>
            $q->where('academic_period_id', $period->id))->count();

        $this->info("Se procesarán: {$enrollments} matrículas · {$items} ítems · {$grades} notas");
        $this->info('');
        $this->warn('⚠  Esta acción es IRREVERSIBLE. Las notas quedarán bloqueadas.');
        $this->info('');

        if (! $this->confirm('¿Confirmas el cierre del período?')) {
            $this->info('Operación cancelada.');
            return self::SUCCESS;
        }

        // Ejecutar cierre
        $this->info('');
        $this->info('Procesando...');

        try {
            $bar = $this->output->createProgressBar(4);
            $bar->setFormat(' %current%/%max% [%bar%] %percent:3s%% — %message%');

            $bar->setMessage('Bloqueando notas...');
            $bar->start();

            $report = $service->close($period, userId: 1);

            $bar->setMessage('Calculando promedios...');
            $bar->advance();

            $bar->setMessage('Cerrando matrículas...');
            $bar->advance();

            $bar->setMessage('Cerrando período...');
            $bar->advance();

            $bar->setMessage('¡Completado!');
            $bar->finish();

            $this->info('');
            $this->info('');
            $this->info('╔══════════════════════════════════════════╗');
            $this->info('║          RESUMEN DEL CIERRE              ║');
            $this->info('╚══════════════════════════════════════════╝');

            $this->table(
                ['Concepto', 'Cantidad'],
                [
                    ['Notas bloqueadas',       $report['locked_grades']],
                    ['Ítems procesados',        $report['locked_items']],
                    ['Materias aprobadas',      $report['approved_items']],
                    ['Materias reprobadas',     $report['failed_items']],
                    ['Matrículas completadas',  $report['completed_enrollments']],
                ]
            );

            if (! empty($report['errors'])) {
                $this->warn('Errores encontrados:');
                foreach ($report['errors'] as $error) {
                    $this->warn("  · {$error}");
                }
            }

            $this->info('');
            $this->info("✓ Período '{$period->name}' cerrado exitosamente.");

            return self::SUCCESS;

        } catch (\Exception $e) {
            $this->error('Error durante el cierre: ' . $e->getMessage());
            return self::FAILURE;
        }
    }
}