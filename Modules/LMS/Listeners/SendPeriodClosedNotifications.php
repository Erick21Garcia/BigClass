<?php

namespace Modules\LMS\Listeners;

use Illuminate\Contracts\Queue\ShouldQueue;
use Modules\Academic\Events\AcademicPeriodClosed;
use Modules\Academic\Models\Enrollment;
use Modules\LMS\Jobs\SendGradeReportEmail;

class SendPeriodClosedNotifications implements ShouldQueue
{
    /**
     * Se ejecuta solo después de que la transacción de ClosePeriodService
     * confirme (defensa extra, además de que el evento ya se dispara
     * después del DB::transaction() en el propio servicio).
     */
    public $afterCommit = true;

    public function handle(AcademicPeriodClosed $event): void
    {
        Enrollment::where('academic_period_id', $event->period->id)
            ->where('status', 'completed')
            ->each(function (Enrollment $enrollment) {
                // Un Job por estudiante — si uno falla (ej: email inválido),
                // no bloquea el envío de los demás.
                SendGradeReportEmail::dispatch($enrollment);
            });
    }
}