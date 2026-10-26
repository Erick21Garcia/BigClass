<?php

namespace Tests\Feature\Academic;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Modules\Academic\Models\Enrollment;
use Modules\Academic\Models\EnrollmentItem;
use Modules\Academic\Services\EnrollmentService;
use Modules\Institucion\Models\Subject;
use Tests\Concerns\CreatesAcademicData;
use Tests\TestCase;

class EnrollmentServiceTest extends TestCase
{
    use RefreshDatabase, CreatesAcademicData;

    private EnrollmentService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = app(EnrollmentService::class);
    }

    public function test_crea_la_matricula_con_section_id_asignado(): void
    {
        $student = $this->makeStudent();
        [$career, $semester] = $this->makeCareerAndSemester();
        $period = $this->makePeriod();
        $curriculum = $this->makeCurriculum($career, $semester);
        $section = $this->makeSection($curriculum, $period);

        $enrollment = $this->service->create(
            $this->enrollmentData($student, $career, $semester, $period, [$curriculum->id])
        );

        $this->assertDatabaseHas('enrollment_items', [
            'enrollment_id' => $enrollment->id,
            'curricula_id'  => $curriculum->id,
            'section_id'    => $section->id,
            'status'        => 'en_curso',
        ]);
    }

    public function test_rechaza_toda_la_matricula_si_falta_paralelo_en_una_materia(): void
    {
        $student = $this->makeStudent();
        [$career, $semester] = $this->makeCareerAndSemester();
        $period = $this->makePeriod();
        $conParalelo = $this->makeCurriculum($career, $semester);
        $sinParalelo = $this->makeCurriculum($career, $semester);
        $this->makeSection($conParalelo, $period);

        try {
            $this->service->create(
                $this->enrollmentData($student, $career, $semester, $period, [$conParalelo->id, $sinParalelo->id])
            );
            $this->fail('Debió lanzar DomainException.');
        } catch (\DomainException $e) {
            $this->assertStringContainsString('No hay un paralelo activo', $e->getMessage());
        }

        $this->assertSame(0, Enrollment::count());
        $this->assertSame(0, EnrollmentItem::count());
    }

    public function test_ignora_paralelos_inactivos_o_de_otro_periodo(): void
    {
        $student = $this->makeStudent();
        [$career, $semester] = $this->makeCareerAndSemester();
        $period = $this->makePeriod();
        $otroPeriod = $this->makePeriod();
        $curriculum = $this->makeCurriculum($career, $semester);

        $this->makeSection($curriculum, $period, ['active' => false]);
        $this->makeSection($curriculum, $otroPeriod);

        $this->expectException(\DomainException::class);

        $this->service->create(
            $this->enrollmentData($student, $career, $semester, $period, [$curriculum->id])
        );
    }

    public function test_con_dos_paralelos_toma_el_de_menor_id(): void
    {
        $student = $this->makeStudent();
        [$career, $semester] = $this->makeCareerAndSemester();
        $period = $this->makePeriod();
        $curriculum = $this->makeCurriculum($career, $semester);
        $primero = $this->makeSection($curriculum, $period, ['name' => 'A']);
        $this->makeSection($curriculum, $period, ['name' => 'B']);

        $enrollment = $this->service->create(
            $this->enrollmentData($student, $career, $semester, $period, [$curriculum->id])
        );

        $this->assertSame($primero->id, $enrollment->items()->first()->section_id);
    }

    public function test_prerequisito_no_aprobado_lanza_domain_exception(): void
    {
        $student = $this->makeStudent();
        [$career, $semester] = $this->makeCareerAndSemester();
        $period = $this->makePeriod();

        $prereq = Subject::create(['code' => 'PRE1', 'name' => 'Base', 'credits' => 4]);
        $curriculum = $this->makeCurriculum($career, $semester);
        $this->makeSection($curriculum, $period);

        DB::table('subject_prerequisites')->insert([
            'subject_id'      => $curriculum->subject_id,
            'prerequisite_id' => $prereq->id,
            'active'          => true,
        ]);

        try {
            $this->service->create(
                $this->enrollmentData($student, $career, $semester, $period, [$curriculum->id])
            );
            $this->fail('Debió lanzar DomainException.');
        } catch (\DomainException $e) {
            $this->assertStringContainsString('Base', $e->getMessage());
        }

        $this->assertSame(0, Enrollment::count());
    }
}