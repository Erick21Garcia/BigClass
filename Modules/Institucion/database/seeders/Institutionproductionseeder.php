<?php

namespace Modules\Institucion\Database\Seeders;

use Illuminate\Database\Seeder;
use Modules\Institucion\Models\Institution;
use Modules\Institucion\Models\Faculty;
use Modules\Institucion\Models\Career;
use Modules\Institucion\Models\Semester;
use Modules\Institucion\Models\Subject;
use Modules\Institucion\Models\Curriculum;

class InstitutionProductionSeeder extends Seeder
{
    public function run(): void
    {
        // ── 1. Institución ────────────────────────────────────────────────────
        $institution = Institution::create([
            'name'     => 'Universidad Técnica del Norte',
            'type'     => 'universidad',
            'code'     => 'UTN',
            'acronym'  => 'UTN',
            'email'    => 'info@utn.edu.ec',
            'phone'    => '062997800',
            'address'  => 'Av. 17 de Julio 5-21 y Gral. José María Córdova',
            'city'     => 'Ibarra',
            'province' => 'Imbabura',
            'country'  => 'Ecuador',
            'active'   => true,
        ]);

        $this->command->info('✓ Institución creada: ' . $institution->name);

        // ── 2. Facultades ─────────────────────────────────────────────────────
        $faculties = [
            [
                'name'        => 'Facultad de Ingeniería en Ciencias Aplicadas',
                'code'        => 'FICA',
                'description' => 'Facultad orientada a las ciencias exactas, tecnología e ingeniería.',
                'dean_name'   => 'Dr. Jorge Caraguay Procel',
            ],
            [
                'name'        => 'Facultad de Ciencias Administrativas y Económicas',
                'code'        => 'FACAE',
                'description' => 'Facultad dedicada a la formación en administración, economía y contabilidad.',
                'dean_name'   => 'Dra. Sandra Guevara Báez',
            ],
            [
                'name'        => 'Facultad de Educación, Ciencia y Tecnología',
                'code'        => 'FECYT',
                'description' => 'Facultad enfocada en la formación docente y ciencias de la educación.',
                'dean_name'   => 'Msc. Pablo Flores Velásquez',
            ],
        ];

        $facultyModels = [];
        foreach ($faculties as $data) {
            $facultyModels[] = Faculty::create(array_merge($data, [
                'institution_id' => $institution->id,
                'active'         => true,
            ]));
        }

        $this->command->info('✓ Facultades creadas: ' . count($facultyModels));

        // ── 3. Carreras (2 por facultad) ──────────────────────────────────────
        $careersData = [
            // FICA
            $facultyModels[0]->id => [
                [
                    'name'               => 'Ingeniería en Sistemas Computacionales',
                    'code'               => 'ISC',
                    'description'        => 'Carrera orientada al desarrollo de software, redes y sistemas de información.',
                    'modality'           => 'presencial',
                    'duration_semesters' => 8,
                    'title_awarded'      => 'Ingeniero/a en Sistemas Computacionales',
                ],
                [
                    'name'               => 'Ingeniería en Electrónica y Redes de Comunicación',
                    'code'               => 'IERC',
                    'description'        => 'Carrera enfocada en telecomunicaciones, electrónica y redes.',
                    'modality'           => 'presencial',
                    'duration_semesters' => 8,
                    'title_awarded'      => 'Ingeniero/a en Electrónica y Redes de Comunicación',
                ],
            ],
            // FACAE
            $facultyModels[1]->id => [
                [
                    'name'               => 'Ingeniería en Administración de Empresas',
                    'code'               => 'IADM',
                    'description'        => 'Carrera orientada a la gestión empresarial y liderazgo organizacional.',
                    'modality'           => 'presencial',
                    'duration_semesters' => 8,
                    'title_awarded'      => 'Ingeniero/a en Administración de Empresas',
                ],
                [
                    'name'               => 'Contabilidad y Auditoría',
                    'code'               => 'CONTA',
                    'description'        => 'Carrera orientada a la contabilidad, finanzas y auditoría empresarial.',
                    'modality'           => 'presencial',
                    'duration_semesters' => 8,
                    'title_awarded'      => 'Licenciado/a en Contabilidad y Auditoría',
                ],
            ],
            // FECYT
            $facultyModels[2]->id => [
                [
                    'name'               => 'Licenciatura en Docencia en Informática',
                    'code'               => 'LDIN',
                    'description'        => 'Formación de docentes especializados en informática educativa.',
                    'modality'           => 'presencial',
                    'duration_semesters' => 8,
                    'title_awarded'      => 'Licenciado/a en Docencia en Informática',
                ],
                [
                    'name'               => 'Licenciatura en Diseño y Publicidad',
                    'code'               => 'LDIP',
                    'description'        => 'Formación en diseño gráfico, comunicación visual y publicidad.',
                    'modality'           => 'presencial',
                    'duration_semesters' => 8,
                    'title_awarded'      => 'Licenciado/a en Diseño y Publicidad',
                ],
            ],
        ];

        $careerModels = [];
        foreach ($careersData as $facultyId => $careers) {
            foreach ($careers as $careerData) {
                $careerModels[] = Career::create(array_merge($careerData, [
                    'faculty_id' => $facultyId,
                    'active'     => true,
                ]));
            }
        }

        $this->command->info('✓ Carreras creadas: ' . count($careerModels));

        // ── 4. Semestres (6 por carrera) ──────────────────────────────────────
        $semesterNames = ['Primer', 'Segundo', 'Tercer', 'Cuarto', 'Quinto', 'Sexto'];

        $semesterModels = [];
        foreach ($careerModels as $career) {
            for ($i = 1; $i <= 6; $i++) {
                $semesterModels[$career->id][] = Semester::create([
                    'career_id' => $career->id,
                    'number'    => $i,
                    'name'      => "{$semesterNames[$i-1]} Semestre",
                    'active'    => true,
                ]);
            }
        }

        $this->command->info('✓ Semestres creados: ' . (count($careerModels) * 6));

        // ── 5. Materias ───────────────────────────────────────────────────────
        // Materias por carrera (agrupadas para reutilizar algunas entre carreras)
        $subjectsData = [
            // Comunes a varias carreras
            ['code' => 'MAT101', 'name' => 'Matemáticas I',                    'credits' => 4],
            ['code' => 'MAT102', 'name' => 'Matemáticas II',                   'credits' => 4],
            ['code' => 'FIS101', 'name' => 'Física I',                         'credits' => 4],
            ['code' => 'FIS102', 'name' => 'Física II',                        'credits' => 4],
            ['code' => 'QUI101', 'name' => 'Química General',                  'credits' => 3],
            ['code' => 'EST101', 'name' => 'Estadística y Probabilidad',       'credits' => 3],
            ['code' => 'ING101', 'name' => 'Inglés Técnico I',                 'credits' => 2],
            ['code' => 'ING102', 'name' => 'Inglés Técnico II',                'credits' => 2],
            ['code' => 'ETI101', 'name' => 'Ética Profesional',                'credits' => 2],
            ['code' => 'INV101', 'name' => 'Metodología de la Investigación',  'credits' => 3],
            // ISC / LDIN
            ['code' => 'PRO101', 'name' => 'Fundamentos de Programación',      'credits' => 4],
            ['code' => 'PRO102', 'name' => 'Programación Orientada a Objetos', 'credits' => 4],
            ['code' => 'PRO201', 'name' => 'Estructura de Datos',              'credits' => 4],
            ['code' => 'PRO202', 'name' => 'Programación Web',                 'credits' => 4],
            ['code' => 'BD101',  'name' => 'Base de Datos I',                  'credits' => 4],
            ['code' => 'BD102',  'name' => 'Base de Datos II',                 'credits' => 4],
            ['code' => 'RED101', 'name' => 'Redes de Computadoras',            'credits' => 3],
            ['code' => 'SO101',  'name' => 'Sistemas Operativos',              'credits' => 3],
            ['code' => 'ING201', 'name' => 'Ingeniería de Software I',         'credits' => 4],
            ['code' => 'ING202', 'name' => 'Ingeniería de Software II',        'credits' => 4],
            ['code' => 'IA101',  'name' => 'Inteligencia Artificial',          'credits' => 4],
            ['code' => 'SEG101', 'name' => 'Seguridad Informática',            'credits' => 3],
            // IERC
            ['code' => 'ELE101', 'name' => 'Electrónica Analógica',            'credits' => 4],
            ['code' => 'ELE102', 'name' => 'Electrónica Digital',              'credits' => 4],
            ['code' => 'COM101', 'name' => 'Comunicaciones I',                 'credits' => 4],
            ['code' => 'COM102', 'name' => 'Comunicaciones II',                'credits' => 4],
            ['code' => 'MIC101', 'name' => 'Microprocesadores',                'credits' => 4],
            // IADM / CONTA
            ['code' => 'ADM101', 'name' => 'Fundamentos de Administración',    'credits' => 3],
            ['code' => 'ADM102', 'name' => 'Administración de Empresas',       'credits' => 3],
            ['code' => 'MKT101', 'name' => 'Marketing I',                      'credits' => 3],
            ['code' => 'MKT102', 'name' => 'Marketing Digital',                'credits' => 3],
            ['code' => 'ECO101', 'name' => 'Economía General',                 'credits' => 3],
            ['code' => 'FIN101', 'name' => 'Finanzas I',                       'credits' => 3],
            ['code' => 'FIN102', 'name' => 'Finanzas II',                      'credits' => 3],
            ['code' => 'CON101', 'name' => 'Contabilidad General',             'credits' => 4],
            ['code' => 'CON102', 'name' => 'Contabilidad de Costos',           'credits' => 4],
            ['code' => 'AUD101', 'name' => 'Auditoría I',                      'credits' => 4],
            ['code' => 'TRI101', 'name' => 'Tributación',                      'credits' => 3],
            // LDIP
            ['code' => 'DIS101', 'name' => 'Diseño Gráfico I',                 'credits' => 4],
            ['code' => 'DIS102', 'name' => 'Diseño Gráfico II',                'credits' => 4],
            ['code' => 'PUB101', 'name' => 'Publicidad y Propaganda',          'credits' => 3],
            ['code' => 'FOT101', 'name' => 'Fotografía Digital',               'credits' => 3],
            ['code' => 'ANI101', 'name' => 'Animación Digital',                'credits' => 4],
        ];

        $subjectModels = [];
        foreach ($subjectsData as $data) {
            $subjectModels[$data['code']] = Subject::create(array_merge($data, ['active' => true]));
        }

        $this->command->info('✓ Materias creadas: ' . count($subjectModels));

        // ── 6. Currículos ─────────────────────────────────────────────────────
        // Definir qué materias van en qué semestre de cada carrera
        $curriculaMap = [
            'ISC' => [
                1 => ['MAT101', 'FIS101', 'PRO101', 'ING101', 'ETI101'],
                2 => ['MAT102', 'FIS102', 'PRO102', 'ING102', 'EST101'],
                3 => ['PRO201', 'BD101',  'SO101',  'RED101', 'QUI101'],
                4 => ['PRO202', 'BD102',  'ING201', 'ELE101', 'EST101'],
                5 => ['ING202', 'IA101',  'COM101', 'SEG101', 'INV101'],
                6 => ['IA101',  'SEG101', 'ING202', 'INV101', 'ETI101'],
            ],
            'IERC' => [
                1 => ['MAT101', 'FIS101', 'ELE101', 'ING101', 'QUI101'],
                2 => ['MAT102', 'FIS102', 'ELE102', 'ING102', 'EST101'],
                3 => ['COM101', 'MIC101', 'RED101', 'PRO101', 'ETI101'],
                4 => ['COM102', 'MIC101', 'BD101',  'SO101',  'INV101'],
                5 => ['COM102', 'SEG101', 'RED101', 'EST101', 'ING102'],
                6 => ['IA101',  'SEG101', 'INV101', 'ETI101', 'COM102'],
            ],
            'IADM' => [
                1 => ['MAT101', 'ADM101', 'ECO101', 'ING101', 'ETI101'],
                2 => ['MAT102', 'ADM102', 'MKT101', 'ING102', 'EST101'],
                3 => ['FIN101', 'CON101', 'MKT102', 'INV101', 'ECO101'],
                4 => ['FIN102', 'CON102', 'AUD101', 'PRO101', 'EST101'],
                5 => ['TRI101', 'AUD101', 'MKT102', 'FIN102', 'INV101'],
                6 => ['AUD101', 'TRI101', 'INV101', 'ETI101', 'FIN102'],
            ],
            'CONTA' => [
                1 => ['MAT101', 'CON101', 'ECO101', 'ING101', 'ETI101'],
                2 => ['MAT102', 'CON102', 'ADM101', 'ING102', 'EST101'],
                3 => ['FIN101', 'AUD101', 'TRI101', 'INV101', 'MKT101'],
                4 => ['FIN102', 'AUD101', 'CON102', 'PRO101', 'EST101'],
                5 => ['TRI101', 'AUD101', 'FIN102', 'MKT102', 'INV101'],
                6 => ['AUD101', 'TRI101', 'INV101', 'ETI101', 'FIN102'],
            ],
            'LDIN' => [
                1 => ['MAT101', 'PRO101', 'ING101', 'ETI101', 'EST101'],
                2 => ['MAT102', 'PRO102', 'ING102', 'INV101', 'BD101'],
                3 => ['PRO201', 'BD102',  'SO101',  'RED101', 'QUI101'],
                4 => ['PRO202', 'ING201', 'IA101',  'SEG101', 'EST101'],
                5 => ['ING202', 'IA101',  'COM101', 'MKT101', 'INV101'],
                6 => ['SEG101', 'ING202', 'INV101', 'ETI101', 'PRO202'],
            ],
            'LDIP' => [
                1 => ['DIS101', 'MAT101', 'ING101', 'ETI101', 'EST101'],
                2 => ['DIS102', 'PUB101', 'ING102', 'FOT101', 'INV101'],
                3 => ['ANI101', 'DIS101', 'MKT101', 'FOT101', 'QUI101'],
                4 => ['ANI101', 'DIS102', 'PUB101', 'MKT102', 'EST101'],
                5 => ['PUB101', 'MKT102', 'FOT101', 'ANI101', 'INV101'],
                6 => ['ANI101', 'DIS102', 'INV101', 'ETI101', 'PUB101'],
            ],
        ];

        $curriculumCount = 0;
        foreach ($careerModels as $career) {
            $map = $curriculaMap[$career->code] ?? null;
            if (! $map) continue;

            foreach ($semesterModels[$career->id] as $semester) {
                $subjectCodes = $map[$semester->number] ?? [];
                // Eliminar duplicados dentro del mismo semestre
                $subjectCodes = array_unique($subjectCodes);

                foreach ($subjectCodes as $code) {
                    if (! isset($subjectModels[$code])) continue;

                    Curriculum::firstOrCreate(
                        [
                            'career_id'  => $career->id,
                            'subject_id' => $subjectModels[$code]->id,
                            'semester_id'=> $semester->id,
                        ],
                        [
                            'is_mandatory' => true,
                            'active'       => true,
                        ]
                    );
                    $curriculumCount++;
                }
            }
        }

        $this->command->info("✓ Currículos creados: {$curriculumCount}");
        $this->command->info('');
        $this->command->info('═══════════════════════════════════════');
        $this->command->info('  InstitutionProductionSeeder DONE ✓  ');
        $this->command->info('═══════════════════════════════════════');
    }
}