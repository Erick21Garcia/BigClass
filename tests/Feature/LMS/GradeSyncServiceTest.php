<?php

namespace Tests\Feature\LMS;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Academic\Models\Grade;
use Modules\LMS\Services\GradeSyncService;
use Tests\Concerns\CreatesAcademicData;
use Tests\TestCase;

class GradeSyncServiceTest extends TestCase
{
    use RefreshDatabase, CreatesAcademicData;

    private GradeSyncService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = app(GradeSyncService::class);
    }

    /** Estudiante matriculado en un paralelo, con su parámetro de evaluación. */
    private function escenario(): array
    {
        $student = $this->makeStudent();
        [$career, $semester] = $this->makeCareerAndSemester();
        $period = $this->makePeriod();
        $curriculum = $this->makeCurriculum($career, $semester);
        $section = $this->makeSection($curriculum, $period);
        $item = $this->makeEnrollmentItem($student, $career, $semester, $period, $curriculum, $section);
        $param = $this->makeEvaluationParameter($period, $curriculum);

        return compact('student', 'section', 'item', 'param');
    }

    public function test_crea_la_nota_en_el_enrollment_item_correcto(): void
    {
        ['student' => $student, 'section' => $section, 'item' => $item, 'param' => $param] = $this->escenario();

        $grade = $this->service->syncScore($section, $student->id, $param->id, 15.5, 'Correcto');

        $this->assertDatabaseHas('grades', [
            'id'                      => $grade->id,
            'enrollment_item_id'      => $item->id,
            'evaluation_parameter_id' => $param->id,
            'score'                   => 15.5,
            'observations'            => 'Correcto',
            'active'                  => true,
        ]);
    }

    public function test_actualiza_la_nota_existente_sin_duplicar(): void
    {
        ['student' => $student, 'section' => $section, 'param' => $param] = $this->escenario();

        $this->service->syncScore($section, $student->id, $param->id, 10);
        $this->service->syncScore($section, $student->id, $param->id, 18, 'Recalificada');

        $this->assertSame(1, Grade::count());
        $this->assertEquals(18, Grade::first()->score);
        $this->assertSame('Recalificada', Grade::first()->observations);
    }

    public function test_falla_si_el_estudiante_no_esta_matriculado_en_la_seccion(): void
    {
        ['section' => $section, 'param' => $param] = $this->escenario();
        $otro = $this->makeStudent();

        try {
            $this->service->syncScore($section, $otro->id, $param->id, 12);
            $this->fail('Debió lanzar RuntimeException.');
        } catch (\RuntimeException $e) {
            $this->assertStringContainsString('No se encontró matrícula', $e->getMessage());
        }

        $this->assertSame(0, Grade::count());
    }

    public function test_falla_si_la_matricula_es_de_otra_seccion(): void
    {
        ['student' => $student, 'section' => $section, 'param' => $param] = $this->escenario();
        $otraSeccion = $this->makeSection($section->curriculum, $section->academicPeriod, ['name' => 'Paralelo B']);

        $this->expectException(\RuntimeException::class);

        $this->service->syncScore($otraSeccion, $student->id, $param->id, 12);
    }

    public function test_no_sobrescribe_una_nota_bloqueada(): void
    {
        ['student' => $student, 'section' => $section, 'item' => $item, 'param' => $param] = $this->escenario();

        Grade::create([
            'enrollment_item_id'      => $item->id,
            'evaluation_parameter_id' => $param->id,
            'score'                   => 14,
            'locked'                  => true,
        ]);

        try {
            $this->service->syncScore($section, $student->id, $param->id, 20);
            $this->fail('Debió lanzar RuntimeException.');
        } catch (\RuntimeException $e) {
            $this->assertStringContainsString('bloqueada', $e->getMessage());
        }

        $this->assertEquals(14, Grade::first()->score);
    }

    public function test_sincroniza_desde_una_entrega_calificada(): void
    {
        ['student' => $student, 'section' => $section, 'item' => $item, 'param' => $param] = $this->escenario();
        $submission = $this->makeSubmission($section, $student, $param);

        $grade = $this->service->syncFromAssignmentSubmission($submission);

        $this->assertSame($item->id, $grade->enrollment_item_id);
        $this->assertSame($param->id, $grade->evaluation_parameter_id);
        $this->assertEquals(17.5, $grade->score);
        $this->assertSame('Buen trabajo', $grade->observations);
    }
}