<?php

namespace Modules\People\Database\Seeders;

use Illuminate\Database\Seeder;
use Modules\People\Models\Person;
use Modules\People\Models\Student;
use Modules\People\Models\Teacher;
use Modules\People\Models\Admin;
use Modules\Institucion\Models\Institution;

class PeopleSeeder extends Seeder
{
    public function run(): void
    {
        $institutions = [
            'UCE-001' => [
                'student' => [
                    'first_name'             => 'Carlos',
                    'second_name'            => 'Andrés',
                    'first_surname'          => 'Morales',
                    'second_surname'         => 'Vega',
                    'identification_number'  => '1720000001',
                    'phone'                  => '022000001',
                    'cellphone'              => '0991000001',
                    'birthdate'              => '2002-03-15',
                    'place_birth'            => 'Quito',
                    'main_street'            => 'Av. América',
                    'secondary_street'       => 'Pérez Guerrero',
                    'neighborhood'           => 'La Mariscal',
                    'reference'              => 'Junto al parque',
                    'marital_status_id'      => 1,
                    'type_identification_id' => 1,
                    'sex_id'                 => 1,
                    'nationality_id'         => 1,
                    'education_level_id'     => 2,
                    'countries_id'           => 1,
                    'provinces_id'           => 1,
                    'cities_id'              => 1,
                ],
                'teacher' => [
                    'first_name'             => 'María',
                    'second_name'            => 'Elena',
                    'first_surname'          => 'Torres',
                    'second_surname'         => 'Ruiz',
                    'identification_number'  => '1720000002',
                    'phone'                  => '022000002',
                    'cellphone'              => '0991000002',
                    'birthdate'              => '1980-07-22',
                    'place_birth'            => 'Quito',
                    'main_street'            => 'Calle Versalles',
                    'secondary_street'       => 'Av. 10 de Agosto',
                    'neighborhood'           => 'El Batán',
                    'reference'              => 'Frente al mercado',
                    'marital_status_id'      => 2,
                    'type_identification_id' => 1,
                    'sex_id'                 => 2,
                    'nationality_id'         => 1,
                    'education_level_id'     => 3,
                    'countries_id'           => 1,
                    'provinces_id'           => 1,
                    'cities_id'              => 1,
                ],
                'admin' => [
                    'first_name'             => 'Luis',
                    'second_name'            => 'Fernando',
                    'first_surname'          => 'Castillo',
                    'second_surname'         => 'Paz',
                    'identification_number'  => '1720000003',
                    'phone'                  => '022000003',
                    'cellphone'              => '0991000003',
                    'birthdate'              => '1975-11-05',
                    'place_birth'            => 'Quito',
                    'main_street'            => 'Av. Shyris',
                    'secondary_street'       => 'Suecia',
                    'neighborhood'           => 'Iñaquito',
                    'reference'              => 'Edificio azul',
                    'marital_status_id'      => 2,
                    'type_identification_id' => 1,
                    'sex_id'                 => 1,
                    'nationality_id'         => 1,
                    'education_level_id'     => 3,
                    'countries_id'           => 1,
                    'provinces_id'           => 1,
                    'cities_id'              => 1,
                ],
            ],
            'UG-002' => [
                'student' => [
                    'first_name'             => 'Gabriela',
                    'second_name'            => 'Paola',
                    'first_surname'          => 'Reyes',
                    'second_surname'         => 'Loor',
                    'identification_number'  => '0920000001',
                    'phone'                  => '042000001',
                    'cellphone'              => '0992000001',
                    'birthdate'              => '2003-01-10',
                    'place_birth'            => 'Guayaquil',
                    'main_street'            => 'Av. 9 de Octubre',
                    'secondary_street'       => 'Pedro Carbo',
                    'neighborhood'           => 'Centro',
                    'reference'              => 'Cerca del malecón',
                    'marital_status_id'      => 1,
                    'type_identification_id' => 1,
                    'sex_id'                 => 2,
                    'nationality_id'         => 1,
                    'education_level_id'     => 2,
                    'countries_id'           => 1,
                    'provinces_id'           => 2,
                    'cities_id'              => 2,
                ],
                'teacher' => [
                    'first_name'             => 'Roberto',
                    'second_name'            => 'José',
                    'first_surname'          => 'Mendoza',
                    'second_surname'         => 'Alvarado',
                    'identification_number'  => '0920000002',
                    'phone'                  => '042000002',
                    'cellphone'              => '0992000002',
                    'birthdate'              => '1978-04-18',
                    'place_birth'            => 'Guayaquil',
                    'main_street'            => 'Av. Francisco de Orellana',
                    'secondary_street'       => 'Miguel H. Alcívar',
                    'neighborhood'           => 'Kennedy Norte',
                    'reference'              => 'Junto al CC Kennedy',
                    'marital_status_id'      => 3,
                    'type_identification_id' => 1,
                    'sex_id'                 => 1,
                    'nationality_id'         => 1,
                    'education_level_id'     => 3,
                    'countries_id'           => 1,
                    'provinces_id'           => 2,
                    'cities_id'              => 2,
                ],
                'admin' => [
                    'first_name'             => 'Patricia',
                    'second_name'            => 'Isabel',
                    'first_surname'          => 'Suárez',
                    'second_surname'         => 'Nieto',
                    'identification_number'  => '0920000003',
                    'phone'                  => '042000003',
                    'cellphone'              => '0992000003',
                    'birthdate'              => '1982-09-30',
                    'place_birth'            => 'Guayaquil',
                    'main_street'            => 'Av. Carlos Julio Arosemena',
                    'secondary_street'       => 'Calle 8va',
                    'neighborhood'           => 'Urdesa',
                    'reference'              => 'Frente al parque Urdesa',
                    'marital_status_id'      => 2,
                    'type_identification_id' => 1,
                    'sex_id'                 => 2,
                    'nationality_id'         => 1,
                    'education_level_id'     => 3,
                    'countries_id'           => 1,
                    'provinces_id'           => 2,
                    'cities_id'              => 2,
                ],
            ],
            'UPE-003' => [
                'student' => [
                    'first_name'             => 'Diego',
                    'second_name'            => 'Sebastián',
                    'first_surname'          => 'Herrera',
                    'second_surname'         => 'Mena',
                    'identification_number'  => '0930000001',
                    'phone'                  => '042100001',
                    'cellphone'              => '0993000001',
                    'birthdate'              => '2001-06-25',
                    'place_birth'            => 'Guayaquil',
                    'main_street'            => 'Km 5 Vía a la Costa',
                    'secondary_street'       => 'Av. Las Aguas',
                    'neighborhood'           => 'Vía a la Costa',
                    'reference'              => 'Frente al campus UPE',
                    'marital_status_id'      => 1,
                    'type_identification_id' => 1,
                    'sex_id'                 => 1,
                    'nationality_id'         => 1,
                    'education_level_id'     => 2,
                    'countries_id'           => 1,
                    'provinces_id'           => 2,
                    'cities_id'              => 2,
                ],
                'teacher' => [
                    'first_name'             => 'Verónica',
                    'second_name'            => 'Cecilia',
                    'first_surname'          => 'Flores',
                    'second_surname'         => 'Bravo',
                    'identification_number'  => '0930000002',
                    'phone'                  => '042100002',
                    'cellphone'              => '0993000002',
                    'birthdate'              => '1976-12-14',
                    'place_birth'            => 'Guayaquil',
                    'main_street'            => 'Av. Rodrigo Chávez',
                    'secondary_street'       => 'Calle Los Pinos',
                    'neighborhood'           => 'Prosperina',
                    'reference'              => 'Casa esquinera verde',
                    'marital_status_id'      => 1,
                    'type_identification_id' => 1,
                    'sex_id'                 => 2,
                    'nationality_id'         => 1,
                    'education_level_id'     => 3,
                    'countries_id'           => 1,
                    'provinces_id'           => 2,
                    'cities_id'              => 2,
                ],
                'admin' => [
                    'first_name'             => 'Jorge',
                    'second_name'            => 'Alberto',
                    'first_surname'          => 'Delgado',
                    'second_surname'         => 'Cruz',
                    'identification_number'  => '0930000003',
                    'phone'                  => '042100003',
                    'cellphone'              => '0993000003',
                    'birthdate'              => '1979-08-03',
                    'place_birth'            => 'Guayaquil',
                    'main_street'            => 'Av. Juan Tanca Marengo',
                    'secondary_street'       => 'Calle Quinta',
                    'neighborhood'           => 'Sauces',
                    'reference'              => 'Junto a la iglesia',
                    'marital_status_id'      => 2,
                    'type_identification_id' => 1,
                    'sex_id'                 => 1,
                    'nationality_id'         => 1,
                    'education_level_id'     => 3,
                    'countries_id'           => 1,
                    'provinces_id'           => 2,
                    'cities_id'              => 2,
                ],
            ],
        ];

        foreach ($institutions as $institutionCode => $roles) {

            $institution = Institution::where('code', $institutionCode)->firstOrFail();

            // ── Estudiante ────────────────────────────────────────────────────
            $studentPerson = Person::updateOrCreate(
                ['identification_number' => $roles['student']['identification_number']],
                $roles['student']
            );

            Student::updateOrCreate(
                ['person_id' => $studentPerson->id],
                [
                    'enrollment_number' => strtoupper($institutionCode) . '-EST-' . $roles['student']['identification_number'],
                    'active'            => true,
                ]
            );

            // ── Docente ───────────────────────────────────────────────────────
            $teacherPerson = Person::updateOrCreate(
                ['identification_number' => $roles['teacher']['identification_number']],
                $roles['teacher']
            );

            Teacher::updateOrCreate(
                ['person_id' => $teacherPerson->id],
                [
                    'institution_id'  => $institution->id,
                    'hire_date'       => now()->subYears(5)->toDateString(),
                    'academic_degree' => 'Magíster',
                    'active'          => true,
                ]
            );

            // ── Administrador ─────────────────────────────────────────────────
            $adminPerson = Person::updateOrCreate(
                ['identification_number' => $roles['admin']['identification_number']],
                $roles['admin']
            );

            Admin::updateOrCreate(
                ['person_id' => $adminPerson->id],
                [
                    'institution_id' => $institution->id,
                    'position'       => 'Director Académico',
                    'active'         => true,
                ]
            );
        }
    }
}