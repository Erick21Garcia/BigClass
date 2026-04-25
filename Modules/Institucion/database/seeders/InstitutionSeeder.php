<?php

namespace Modules\Institucion\Database\Seeders;

use Illuminate\Database\Seeder;
use Modules\Institucion\Models\Institution;
use Modules\Institucion\Models\Faculty;
use Modules\Institucion\Models\Career;
use Modules\Institucion\Models\Semester;

class InstitutionSeeder extends Seeder
{
    public function run(): void
    {
        $institutions = [
            [
                'institution' => [
                    'name'     => 'Universidad Central del Ecuador',
                    'type'     => 'Pública',
                    'code'     => 'UCE-001',
                    'acronym'  => 'UCE',
                    'email'    => 'info@uce.edu.ec',
                    'phone'    => '0990000010',
                    'address'  => 'Av. América y Pérez Guerrero',
                    'city'     => 'Quito',
                    'province' => 'Pichincha',
                    'country'  => 'Ecuador',
                    'active'   => true,
                ],
                'faculties' => [
                    [
                        'name' => 'Facultad de Ingeniería',
                        'code' => 'FI-UCE',
                        'careers' => [
                            ['name' => 'Ingeniería en Sistemas',          'code' => 'IS-UCE',  'title' => 'Ingeniero en Sistemas'],
                            ['name' => 'Ingeniería Civil',                'code' => 'IC-UCE',  'title' => 'Ingeniero Civil'],
                            ['name' => 'Ingeniería Eléctrica',            'code' => 'IE-UCE',  'title' => 'Ingeniero Eléctrico'],
                        ],
                    ],
                    [
                        'name' => 'Facultad de Ciencias Económicas',
                        'code' => 'FCE-UCE',
                        'careers' => [
                            ['name' => 'Economía',                        'code' => 'ECO-UCE', 'title' => 'Economista'],
                            ['name' => 'Administración de Empresas',      'code' => 'ADE-UCE', 'title' => 'Ingeniero en Administración'],
                            ['name' => 'Contabilidad y Auditoría',        'code' => 'CYA-UCE', 'title' => 'Doctor en Contabilidad y Auditoría'],
                        ],
                    ],
                ],
            ],
            [
                'institution' => [
                    'name'     => 'Universidad de Guayaquil',
                    'type'     => 'Pública',
                    'code'     => 'UG-002',
                    'acronym'  => 'UG',
                    'email'    => 'info@ug.edu.ec',
                    'phone'    => '0990000020',
                    'address'  => 'Ciudadela Universitaria Salvador Allende',
                    'city'     => 'Guayaquil',
                    'province' => 'Guayas',
                    'country'  => 'Ecuador',
                    'active'   => true,
                ],
                'faculties' => [
                    [
                        'name' => 'Facultad de Ciencias Matemáticas y Físicas',
                        'code' => 'FCMF-UG',
                        'careers' => [
                            ['name' => 'Ingeniería en Networking',        'code' => 'NET-UG',  'title' => 'Ingeniero en Networking'],
                            ['name' => 'Ingeniería en Telecomunicaciones','code' => 'TEL-UG',  'title' => 'Ingeniero en Telecomunicaciones'],
                            ['name' => 'Ingeniería Industrial',           'code' => 'IIN-UG',  'title' => 'Ingeniero Industrial'],
                        ],
                    ],
                    [
                        'name' => 'Facultad de Ciencias Psicológicas',
                        'code' => 'FCP-UG',
                        'careers' => [
                            ['name' => 'Psicología Clínica',              'code' => 'PSC-UG',  'title' => 'Psicólogo Clínico'],
                            ['name' => 'Psicología Industrial',           'code' => 'PSI-UG',  'title' => 'Psicólogo Industrial'],
                            ['name' => 'Orientación Vocacional',          'code' => 'OVO-UG',  'title' => 'Licenciado en Orientación Vocacional'],
                        ],
                    ],
                ],
            ],
            [
                'institution' => [
                    'name'     => 'Universidad del Pacífico del Ecuador',
                    'type'     => 'Privada',
                    'code'     => 'UPE-003',
                    'acronym'  => 'UPE',
                    'email'    => 'info@upe.edu.ec',
                    'phone'    => '0990000030',
                    'address'  => 'Km 5 Vía a la Costa',
                    'city'     => 'Guayaquil',
                    'province' => 'Guayas',
                    'country'  => 'Ecuador',
                    'active'   => true,
                ],
                'faculties' => [
                    [
                        'name' => 'Facultad de Negocios',
                        'code' => 'FN-UPE',
                        'careers' => [
                            ['name' => 'Comercio Exterior',               'code' => 'CEX-UPE', 'title' => 'Ingeniero en Comercio Exterior'],
                            ['name' => 'Marketing y Publicidad',          'code' => 'MKT-UPE', 'title' => 'Licenciado en Marketing'],
                            ['name' => 'Finanzas y Banca',                'code' => 'FYB-UPE', 'title' => 'Ingeniero en Finanzas'],
                        ],
                    ],
                    [
                        'name' => 'Facultad de Tecnología',
                        'code' => 'FT-UPE',
                        'careers' => [
                            ['name' => 'Ingeniería en Software',          'code' => 'ISW-UPE', 'title' => 'Ingeniero en Software'],
                            ['name' => 'Diseño Digital y Multimedia',     'code' => 'DDM-UPE', 'title' => 'Licenciado en Diseño Digital'],
                            ['name' => 'Ciberseguridad',                  'code' => 'CSE-UPE', 'title' => 'Ingeniero en Ciberseguridad'],
                        ],
                    ],
                ],
            ],
        ];

        foreach ($institutions as $entry) {

            // =============================
            // INSTITUCIÓN
            // =============================
            $institution = Institution::updateOrCreate(
                ['code' => $entry['institution']['code']],
                $entry['institution']
            );

            foreach ($entry['faculties'] as $facultyData) {

                // =============================
                // FACULTAD
                // =============================
                $faculty = Faculty::updateOrCreate(
                    [
                        'institution_id' => $institution->id,
                        'code'           => $facultyData['code'],
                    ],
                    [
                        'name'   => $facultyData['name'],
                        'active' => true,
                    ]
                );

                foreach ($facultyData['careers'] as $careerData) {

                    // =============================
                    // CARRERA
                    // =============================
                    $career = Career::updateOrCreate(
                        [
                            'faculty_id' => $faculty->id,
                            'code'       => $careerData['code'],
                        ],
                        [
                            'name'               => $careerData['name'],
                            'modality'           => 'Presencial',
                            'duration_semesters' => 6,
                            'title_awarded'      => $careerData['title'],
                            'active'             => true,
                        ]
                    );

                    // =============================
                    // SEMESTRES (6 por carrera)
                    // =============================
                    for ($i = 1; $i <= 6; $i++) {
                        Semester::updateOrCreate(
                            [
                                'career_id' => $career->id,
                                'number'    => $i,
                            ],
                            [
                                'name'   => "Semestre {$i}",
                                'active' => true,
                            ]
                        );
                    }
                }
            }
        }
    }
}