<?php

namespace Tests\Concerns;

use App\Models\User;
use Illuminate\Support\Facades\DB;
use Modules\Academic\Models\AcademicPeriod;
use Modules\Academic\Models\Section;
use Modules\Admissions\Models\Applicant;
use Modules\Academic\Models\Enrollment;
use Modules\Academic\Models\EnrollmentItem;
use Modules\Academic\Models\EvaluationParameter;
use Modules\LMS\Models\Assignment;
use Modules\LMS\Models\AssignmentSubmission;
use Modules\LMS\Models\Unit;
use Modules\LMS\Models\VirtualCourse;
use Modules\Institucion\Models\Career;
use Modules\Institucion\Models\Curriculum;
use Modules\Institucion\Models\Faculty;
use Modules\Institucion\Models\Semester;
use Modules\Institucion\Models\Subject;
use Modules\People\Models\Person;
use Modules\People\Models\Student;

trait CreatesAcademicData
{
    private int $seq = 0;
    private ?array $catalogIds = null;

    protected function next(): int
    {
        return ++$this->seq;
    }

    /** Catálogos mínimos que exige `people` (8 FKs). */
    protected function catalogs(): array
    {
        if ($this->catalogIds) {
            return $this->catalogIds;
        }

        $country  = DB::table('countries')->insertGetId(['name' => 'Ecuador']);
        $province = DB::table('provinces')->insertGetId(['country_id' => $country, 'name' => 'Pichincha']);

        return $this->catalogIds = [
            'marital_status_id'      => DB::table('marital_statuses')->insertGetId(['name' => 'Soltero']),
            'type_identification_id' => DB::table('type_identifications')->insertGetId(['name' => 'Cédula']),
            'sex_id'                 => DB::table('sexes')->insertGetId(['name' => 'Masculino']),
            'nationality_id'         => DB::table('nationalities')->insertGetId(['country_id' => $country, 'name' => 'Ecuatoriana']),
            'education_level_id'     => DB::table('education_levels')->insertGetId(['name' => 'Bachillerato']),
            'countries_id'           => $country,
            'provinces_id'           => $province,
            'cities_id'              => DB::table('cities')->insertGetId(['province_id' => $province, 'name' => 'Quito']),
        ];
    }

    protected function makeStudent(): Student
    {
        $n = $this->next();

        $person = Person::create($this->catalogs() + [
            'first_name'            => "Nombre{$n}",
            'first_surname'         => "Apellido{$n}",
            'identification_number' => str_pad((string) $n, 10, '0', STR_PAD_LEFT),
        ]);

        return Student::create([
            'person_id'         => $person->id,
            'enrollment_number' => '2026-' . str_pad((string) $n, 5, '0', STR_PAD_LEFT),
            'active'            => true,
        ]);
    }

    protected function makeApplicant(array $overrides = []): Applicant
    {
        $n = $this->next();
        [$career] = $this->makeCareerAndSemester();

        return Applicant::create($overrides + $this->catalogs() + [
            'user_id'               => User::factory()->create()->id,
            'career_id'             => $career->id,
            'first_name'            => "Aspirante{$n}",
            'first_surname'         => "Apellido{$n}",
            'identification_number' => '17' . str_pad((string) $n, 8, '0', STR_PAD_LEFT),
            'birthdate'             => '2000-01-15',
            'status'                => 'aprobada',
        ]);
    }

    /** @return array{0: Career, 1: Semester} */
    protected function makeCareerAndSemester(): array
    {
        $n = $this->next();

        // AJUSTAR si `institutions` tiene otras columnas NOT NULL.
        $institutionId = DB::table('institutions')->insertGetId([
            'name' => "Instituto {$n}",
            'code' => "INS{$n}",
        ]);

        $faculty = Faculty::create(['institution_id' => $institutionId, 'name' => "Facultad {$n}", 'code' => "FAC{$n}"]);

        $career = Career::create([
            'faculty_id'         => $faculty->id,
            'name'               => "Carrera {$n}",
            'code'               => "CAR{$n}",
            'modality'           => 'presencial',
            'duration_semesters' => 6,
            'title_awarded'      => 'Tecnólogo',
        ]);

        $semester = Semester::create(['career_id' => $career->id, 'number' => 1, 'name' => 'Semestre 1']);

        return [$career, $semester];
    }

    protected function makeCurriculum(Career $career, Semester $semester, ?Subject $subject = null): Curriculum
    {
        $n = $this->next();

        $subject ??= Subject::create(['code' => "MAT{$n}", 'name' => "Materia {$n}", 'credits' => 4]);

        return Curriculum::create([
            'career_id'   => $career->id,
            'subject_id'  => $subject->id,
            'semester_id' => $semester->id,
        ]);
    }

    protected function makePeriod(): AcademicPeriod
    {
        return AcademicPeriod::create([
            'name'       => 'Periodo ' . $this->next(),
            'start_date' => now()->startOfMonth(),
            'end_date'   => now()->addMonths(5),
            'active'     => true,
            'status'     => 'active',
        ]);
    }

    protected function makeSection(Curriculum $curriculum, AcademicPeriod $period, array $overrides = []): Section
    {
        return Section::create($overrides + [
            'curricula_id'       => $curriculum->id,
            'teacher_id'         => User::factory()->create()->id,
            'academic_period_id' => $period->id,
            'name'               => 'Paralelo A',
            'quota'              => 30,
            'active'             => true,
        ]);
    }

    protected function makeEnrollmentItem(Student $student, Career $career, Semester $semester, AcademicPeriod $period, Curriculum $curriculum, Section $section): EnrollmentItem
    {
        $enrollment = Enrollment::create($this->enrollmentData($student, $career, $semester, $period, []));

        return EnrollmentItem::create([
            'enrollment_id' => $enrollment->id,
            'curricula_id'  => $curriculum->id,
            'section_id'    => $section->id,
            'status'        => 'en_curso',
            'active'        => true,
        ]);
    }

    protected function makeEvaluationParameter(AcademicPeriod $period, ?Curriculum $curriculum = null): EvaluationParameter
    {
        return EvaluationParameter::create([
            'academic_period_id' => $period->id,
            'curriculum_id'      => $curriculum?->id,
            'name'               => 'Parametro ' . $this->next(),
            'percentage'         => 100,
        ]);
    }

    protected function makeSubmission(Section $section, Student $student, EvaluationParameter $param, array $overrides = []): AssignmentSubmission
    {
        $course = VirtualCourse::create(['section_id' => $section->id]);
        $unit = Unit::create(['virtual_course_id' => $course->id, 'name' => 'Unidad 1']);

        $assignment = Assignment::create([
            'unit_id'                 => $unit->id,
            'evaluation_parameter_id' => $param->id,
            'title'                   => 'Tarea 1',
            'due_date'                => now()->addWeek(),
        ]);

        return AssignmentSubmission::create($overrides + [
            'assignment_id' => $assignment->id,
            'student_id'    => $student->id,
            'submitted_at'  => now(),
            'grade'         => 17.5,
            'feedback'      => 'Buen trabajo',
        ]);
    }

    protected function enrollmentData(Student $student, Career $career, Semester $semester, AcademicPeriod $period, array $curriculaIds): array
    {
        return [
            'student_id'         => $student->id,
            'career_id'          => $career->id,
            'semester_id'        => $semester->id,
            'academic_period_id' => $period->id,
            'enrollment_date'    => now()->toDateString(),
            'type'               => 'regular',
            'status'             => 'active',
            'curricula_ids'      => $curriculaIds,
        ];
    }
}