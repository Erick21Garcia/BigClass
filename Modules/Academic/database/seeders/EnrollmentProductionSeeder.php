<?php

namespace Modules\Academic\Database\Seeders;

use Illuminate\Database\Seeder;
use Modules\Academic\Models\AcademicPeriod;
use Modules\Academic\Models\Enrollment;
use Modules\Academic\Models\EnrollmentItem;
use Modules\Academic\Models\EvaluationParameter;
use Modules\Academic\Models\Grade;
use Modules\Academic\Models\Schedule;
use Modules\Academic\Models\Section;
use Modules\Institucion\Models\Career;
use Modules\Institucion\Models\Classroom;
use Modules\Institucion\Models\Curriculum;
use Modules\Institucion\Models\Semester;
use Modules\People\Models\Student;
use Modules\People\Models\Teacher;

class EnrollmentProductionSeeder extends Seeder
{
    // Horarios disponibles: [día, inicio, fin]
    // día: 1=Lunes...5=Viernes
    private array $timeSlots = [
        [1, '07:00', '09:00'],
        [1, '09:00', '11:00'],
        [1, '11:00', '13:00'],
        [1, '14:00', '16:00'],
        [1, '16:00', '18:00'],
        [2, '07:00', '09:00'],
        [2, '09:00', '11:00'],
        [2, '11:00', '13:00'],
        [2, '14:00', '16:00'],
        [2, '16:00', '18:00'],
        [3, '07:00', '09:00'],
        [3, '09:00', '11:00'],
        [3, '11:00', '13:00'],
        [3, '14:00', '16:00'],
        [3, '16:00', '18:00'],
        [4, '07:00', '09:00'],
        [4, '09:00', '11:00'],
        [4, '11:00', '13:00'],
        [4, '14:00', '16:00'],
        [4, '16:00', '18:00'],
        [5, '07:00', '09:00'],
        [5, '09:00', '11:00'],
        [5, '11:00', '13:00'],
        [5, '14:00', '16:00'],
        [5, '16:00', '18:00'],
    ];

    private array $usedSlots = []; // classroom_id => [day => [slots usados]]

    public function run(): void
    {
        $teachers   = Teacher::where('active', true)->get();
        $students   = Student::where('active', true)->get();
        $classrooms = Classroom::where('active', true)->get();
        $periods    = AcademicPeriod::orderBy('id')->get();

        if ($teachers->isEmpty() || $students->isEmpty() || $classrooms->isEmpty() || $periods->isEmpty()) {
            $this->command->error('Faltan datos base. Ejecuta los seeders previos primero.');
            return;
        }

        $careers = Career::where('active', true)->with('semesters')->get();

        // ── Por cada período ──────────────────────────────────────────────────
        foreach ($periods as $periodIndex => $period) {
            $this->command->info("");
            $this->command->info("── Período: {$period->name} ──");
            $this->usedSlots = [];

            // ── Crear secciones y horarios ────────────────────────────────────
            // Solo para los primeros 3 semestres de cada carrera para tener datos manejables
            $teacherIndex = 0;
            $slotIndex    = 0;

            foreach ($careers as $career) {
                $semesters = $career->semesters->sortBy('number')->take(3);

                foreach ($semesters as $semester) {
                    $curricula = Curriculum::where('semester_id', $semester->id)
                        ->where('active', true)
                        ->with('subject')
                        ->get();

                    foreach ($curricula as $curriculum) {
                        $teacher   = $teachers[$teacherIndex % $teachers->count()];
                        $teacherIndex++;

                        // Crear sección
                        $section = Section::firstOrCreate(
                            [
                                'curricula_id'       => $curriculum->id,
                                'academic_period_id' => $period->id,
                                'teacher_id'         => $teacher->id,
                            ],
                            [
                                'name'   => 'Sección A',
                                'quota'  => 35,
                                'active' => true,
                            ]
                        );

                        // Asignar horario evitando conflictos de aula
                        $classroom = $classrooms[$slotIndex % $classrooms->count()];
                        $slot      = $this->getAvailableSlot($classroom->id, $slotIndex);
                        $slotIndex++;

                        if ($slot) {
                            Schedule::firstOrCreate(
                                ['section_id' => $section->id, 'day_of_week' => $slot[0]],
                                [
                                    'classroom_id'   => $classroom->id,
                                    'start_time'     => $slot[1],
                                    'end_time'       => $slot[2],
                                    'is_recurring'   => true,
                                    'specific_date'  => null,
                                    'recurrence_end' => $period->end_date,
                                    'active'         => true,
                                ]
                            );
                        }
                    }
                }
            }

            $this->command->info("  ✓ Secciones y horarios creados para {$period->name}");

            // ── Matricular estudiantes ────────────────────────────────────────
            // Distribuir 50 estudiantes entre las 6 carreras (~8 por carrera)
            $studentsPerCareer = $students->chunk((int) ceil($students->count() / $careers->count()));
            $careerIndex       = 0;

            foreach ($careers as $career) {
                $careerStudents = $studentsPerCareer[$careerIndex] ?? collect();
                $careerIndex++;

                // Semestres activos para esta carrera en este período (primeros 3)
                $semesters = $career->semesters->sortBy('number')->take(3);

                // Distribuir estudiantes entre semestres
                $studentsPerSemester = $careerStudents->chunk(
                    max(1, (int) ceil($careerStudents->count() / $semesters->count()))
                );
                $semesterIndex = 0;

                foreach ($semesters as $semester) {
                    $semStudents = $studentsPerSemester[$semesterIndex] ?? collect();
                    $semesterIndex++;

                    foreach ($semStudents as $student) {
                        // Verificar si ya tiene matrícula en este período/carrera/semestre
                        $existing = Enrollment::where('student_id', $student->id)
                            ->where('academic_period_id', $period->id)
                            ->where('career_id', $career->id)
                            ->first();

                        if ($existing) continue;

                        $enrollment = Enrollment::create([
                            'student_id'        => $student->id,
                            'career_id'         => $career->id,
                            'semester_id'       => $semester->id,
                            'academic_period_id'=> $period->id,
                            'enrollment_date'   => $period->start_date->addDays(rand(0, 5)),
                            'type'              => 'regular',
                            'status'            => $period->active ? 'active' : 'completed',
                        ]);

                        // Crear enrollment items por cada materia del semestre
                        $curricula = Curriculum::where('semester_id', $semester->id)
                            ->where('active', true)
                            ->get();

                        foreach ($curricula as $curriculum) {
                            // Buscar la sección correspondiente
                            $section = Section::where('curricula_id', $curriculum->id)
                                ->where('academic_period_id', $period->id)
                                ->first();

                            $item = EnrollmentItem::create([
                                'enrollment_id' => $enrollment->id,
                                'curricula_id'  => $curriculum->id,
                                'section_id'    => $section?->id,
                                'status'        => 'en_curso',
                                'final_grade'   => null,
                                'active'        => true,
                            ]);

                            // Asignar notas
                            $this->assignGrades($item, $period, $periodIndex);
                        }
                    }
                }
            }

            $this->command->info("  ✓ Matrículas y notas creadas para {$period->name}");
        }

        $this->command->info('');
        $this->command->info('══════════════════════════════════════════');
        $this->command->info('  EnrollmentProductionSeeder DONE ✓      ');
        $this->command->info('══════════════════════════════════════════');
        $this->command->info('  Enrollments: ' . Enrollment::count());
        $this->command->info('  EnrollmentItems: ' . EnrollmentItem::count());
        $this->command->info('  Grades: ' . Grade::count());
        $this->command->info('  Sections: ' . Section::count());
        $this->command->info('  Schedules: ' . Schedule::count());
    }

    private function assignGrades(EnrollmentItem $item, AcademicPeriod $period, int $periodIndex): void
    {
        $parameters = EvaluationParameter::where('academic_period_id', $period->id)
            ->whereNull('curriculum_id')
            ->where('active', true)
            ->get();

        if ($parameters->isEmpty()) return;

        // Período cerrado → notas completas
        // Período activo → solo primer parcial calificado
        $isActive     = $period->active;
        $paramCount   = $parameters->count();
        $gradedParams = $isActive ? 1 : $paramCount;

        $totalWeighted = 0;
        $hasAllGrades  = true;
        $gradesGiven   = 0;

        foreach ($parameters as $index => $param) {
            if ($gradesGiven >= $gradedParams) {
                $hasAllGrades = false;
                break;
            }

            // Generar nota realista (mayoría aprueban, ~20% reprueban)
            if (rand(1, 100) <= 20) {
                // Reprobado: 0-6.9
                $score = round(rand(0, 69) / 10, 2);
            } else {
                // Aprobado: 7.0-10.0
                $score = round(rand(70, 100) / 10, 2);
            }

            Grade::create([
                'enrollment_item_id'      => $item->id,
                'evaluation_parameter_id' => $param->id,
                'score'                   => $score,
                'observations'            => null,
                'active'                  => true,
            ]);

            $totalWeighted += ($score * $param->percentage) / 100;
            $gradesGiven++;
        }

        // Calcular nota final solo si tiene todas las notas
        if ($hasAllGrades && $gradesGiven === $paramCount) {
            $finalGrade = round($totalWeighted, 2);
            $status     = $finalGrade >= 7 ? 'aprobado' : 'reprobado';

            $item->update([
                'final_grade' => $finalGrade,
                'status'      => $status,
            ]);
        }
    }

    private function getAvailableSlot(int $classroomId, int $fallbackIndex): ?array
    {
        // Intentar encontrar un slot libre para esta aula
        foreach ($this->timeSlots as $slot) {
            [$day, $start, $end] = $slot;
            $key = "{$classroomId}-{$day}-{$start}";
            if (! isset($this->usedSlots[$key])) {
                $this->usedSlots[$key] = true;
                return $slot;
            }
        }

        // Si todos están ocupados, devolver uno de todos modos (habrá conflicto, OK para demo)
        return $this->timeSlots[$fallbackIndex % count($this->timeSlots)];
    }
}