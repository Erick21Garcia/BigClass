<?php

namespace Tests\Feature\Admissions;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Academic\Models\Enrollment;
use Modules\Admissions\Services\AdmissionConversionService;
use Modules\People\Models\Person;
use Modules\People\Models\Student;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;
use Tests\Concerns\CreatesAcademicData;
use Tests\TestCase;

class AdmissionConversionServiceTest extends TestCase
{
    use RefreshDatabase, CreatesAcademicData;

    private AdmissionConversionService $service;

    protected function setUp(): void
    {
        parent::setUp();

        Role::firstOrCreate(['name' => 'aspirante']);
        Role::firstOrCreate(['name' => 'estudiante']);
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        $this->service = app(AdmissionConversionService::class);
    }

    public function test_convierte_creando_person_student_y_rol_sin_matricula(): void
    {
        $applicant = $this->makeApplicant();
        $applicant->user->assignRole('aspirante');

        $student = $this->service->convertToStudent($applicant);

        $this->assertDatabaseHas('people', [
            'user_id'               => $applicant->user_id,
            'identification_number' => $applicant->identification_number,
            'first_name'            => $applicant->first_name,
        ]);
        $this->assertSame($student->person->id, Person::where('user_id', $applicant->user_id)->value('id'));
        $this->assertTrue($student->active);

        $user = $applicant->user->fresh();
        $this->assertTrue($user->hasRole('estudiante'));
        $this->assertFalse($user->hasRole('aspirante'));

        $this->assertSame(0, Enrollment::count());
    }

    public function test_rechaza_postulaciones_que_no_estan_aprobadas(): void
    {
        foreach (['borrador', 'recibida', 'en_revision', 'rechazada', 'abandonada'] as $status) {
            $applicant = $this->makeApplicant(['status' => $status]);

            try {
                $this->service->convertToStudent($applicant);
                $this->fail("Debió rechazar el estado {$status}.");
            } catch (\RuntimeException $e) {
                $this->assertStringContainsString('aprobada', $e->getMessage());
            }
        }

        $this->assertSame(0, Person::count());
        $this->assertSame(0, Student::count());
    }

    public function test_no_permite_convertir_dos_veces(): void
    {
        $applicant = $this->makeApplicant();
        $this->service->convertToStudent($applicant);

        try {
            $this->service->convertToStudent($applicant);
            $this->fail('Debió lanzar RuntimeException.');
        } catch (\RuntimeException $e) {
            $this->assertStringContainsString('ya tiene un Person', $e->getMessage());
        }

        $this->assertSame(1, Person::count());
        $this->assertSame(1, Student::count());
    }

    public function test_si_falla_el_rol_hace_rollback_de_person_y_student(): void
    {
        $applicant = $this->makeApplicant();
        $applicant->user->assignRole('aspirante');

        // Sin el rol 'estudiante', syncRoles falla dentro de la transacción.
        Role::where('name', 'estudiante')->delete();
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        try {
            $this->service->convertToStudent($applicant);
            $this->fail('Debió lanzar excepción por rol inexistente.');
        } catch (\Throwable $e) {
            $this->assertInstanceOf(\Spatie\Permission\Exceptions\RoleDoesNotExist::class, $e);
        }

        $this->assertSame(0, Person::count());
        $this->assertSame(0, Student::count());
        $this->assertTrue($applicant->user->fresh()->hasRole('aspirante'));
    }

    public function test_genera_numeros_de_matricula_secuenciales_por_anio(): void
    {
        $primero = $this->service->convertToStudent($this->makeApplicant());
        $segundo = $this->service->convertToStudent($this->makeApplicant());

        $year = now()->year;
        $this->assertSame("{$year}-00001", $primero->enrollment_number);
        $this->assertSame("{$year}-00002", $segundo->enrollment_number);
    }
}