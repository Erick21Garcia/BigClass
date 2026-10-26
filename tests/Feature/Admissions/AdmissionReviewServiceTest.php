<?php

namespace Tests\Feature\Admissions;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Modules\Admissions\Mail\ApplicationDecisionMail;
use Modules\Admissions\Services\AdmissionReviewService;
use Modules\People\Models\Person;
use Modules\People\Models\Student;
use Spatie\Permission\Exceptions\RoleDoesNotExist;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;
use Tests\Concerns\CreatesAcademicData;
use Tests\TestCase;

class AdmissionReviewServiceTest extends TestCase
{
    use RefreshDatabase, CreatesAcademicData;

    private AdmissionReviewService $service;
    private User $staff;

    protected function setUp(): void
    {
        parent::setUp();

        Role::firstOrCreate(['name' => 'aspirante']);
        Role::firstOrCreate(['name' => 'estudiante']);
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        Mail::fake();

        $this->staff = User::factory()->create();
        $this->service = app(AdmissionReviewService::class);
    }

    public function test_aprobar_convierte_registra_decision_y_encola_el_correo(): void
    {
        $applicant = $this->makeApplicant(['status' => 'en_revision']);
        $applicant->user->assignRole('aspirante');

        $this->service->moveStatus($applicant, 'aprobada', 'Cumple requisitos', $this->staff->id);

        $applicant->refresh();
        $this->assertSame('aprobada', $applicant->status);
        $this->assertSame('Cumple requisitos', $applicant->decision_notes);
        $this->assertNotNull($applicant->decided_at);
        $this->assertSame($this->staff->id, $applicant->decided_by);

        $this->assertSame(1, Person::where('user_id', $applicant->user_id)->count());
        $this->assertSame(1, Student::count());
        $this->assertTrue($applicant->user->fresh()->hasRole('estudiante'));

        Mail::assertQueued(ApplicationDecisionMail::class, 1);
        Mail::assertQueued(ApplicationDecisionMail::class, fn ($m) => $m->hasTo($applicant->user->email));
    }

    public function test_rechazar_encola_el_correo_y_no_crea_person_ni_student(): void
    {
        $applicant = $this->makeApplicant(['status' => 'en_revision']);

        $this->service->moveStatus($applicant, 'rechazada', 'Documentos ilegibles', $this->staff->id);

        $applicant->refresh();
        $this->assertSame('rechazada', $applicant->status);
        $this->assertSame($this->staff->id, $applicant->decided_by);
        $this->assertSame(0, Person::count());
        $this->assertSame(0, Student::count());

        Mail::assertQueued(ApplicationDecisionMail::class, 1);
    }

    public function test_mover_a_en_revision_no_envia_correo_ni_registra_decision(): void
    {
        $applicant = $this->makeApplicant(['status' => 'recibida']);

        $this->service->moveStatus($applicant, 'en_revision', null, $this->staff->id);

        $applicant->refresh();
        $this->assertSame('en_revision', $applicant->status);
        $this->assertNull($applicant->decided_at);
        $this->assertNull($applicant->decided_by);

        Mail::assertNothingQueued();
    }

    public function test_si_falla_la_conversion_hace_rollback_y_no_envia_correo(): void
    {
        $applicant = $this->makeApplicant(['status' => 'en_revision']);
        $applicant->user->assignRole('aspirante');

        Role::where('name', 'estudiante')->delete();
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        try {
            $this->service->moveStatus($applicant, 'aprobada', null, $this->staff->id);
            $this->fail('Debió lanzar RoleDoesNotExist.');
        } catch (\Throwable $e) {
            $this->assertInstanceOf(RoleDoesNotExist::class, $e);
        }

        $fresh = $applicant->fresh();
        $this->assertSame('en_revision', $fresh->status);
        $this->assertNull($fresh->decided_at);
        $this->assertSame(0, Person::count());
        $this->assertSame(0, Student::count());

        Mail::assertNothingQueued();
    }

    public function test_rechaza_un_estado_invalido(): void
    {
        $applicant = $this->makeApplicant(['status' => 'recibida']);

        try {
            $this->service->moveStatus($applicant, 'inventado', null, $this->staff->id);
            $this->fail('Debió lanzar RuntimeException.');
        } catch (\RuntimeException $e) {
            $this->assertStringContainsString('Estado inválido', $e->getMessage());
        }

        $this->assertSame('recibida', $applicant->fresh()->status);
        Mail::assertNothingQueued();
    }

    public function test_no_mueve_postulaciones_en_borrador_o_abandonadas(): void
    {
        foreach (['borrador', 'abandonada'] as $status) {
            $applicant = $this->makeApplicant(['status' => $status]);

            try {
                $this->service->moveStatus($applicant, 'aprobada', null, $this->staff->id);
                $this->fail("Debió rechazar el estado {$status}.");
            } catch (\RuntimeException $e) {
                $this->assertStringContainsString('no está en el flujo de revisión', $e->getMessage());
            }

            $this->assertSame($status, $applicant->fresh()->status);
        }

        $this->assertSame(0, Student::count());
        Mail::assertNothingQueued();
    }
}