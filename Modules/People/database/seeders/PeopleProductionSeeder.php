<?php

namespace Modules\People\Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Modules\People\Models\Person;
use Modules\People\Models\Teacher;
use Modules\People\Models\Student;
use Modules\People\Models\Admin;
use Modules\Institucion\Models\Institution;
use App\Models\User;

class PeopleProductionSeeder extends Seeder
{
    // Nombres ecuatorianos realistas
    private array $firstNames = [
        'Carlos', 'María', 'José', 'Ana', 'Luis', 'Patricia', 'Jorge', 'Sandra',
        'Roberto', 'Verónica', 'Diego', 'Gabriela', 'Andrés', 'Fernanda', 'Ricardo',
        'Claudia', 'Sebastián', 'Valeria', 'Miguel', 'Daniela', 'Eduardo', 'Natalia',
        'Pablo', 'Cristina', 'Iván', 'Lorena', 'Francisco', 'Alexandra', 'Javier', 'Mónica',
        'Kevin', 'Samantha', 'Bryan', 'Estefanía', 'Steven', 'Pamela', 'Jonathan', 'Karla',
        'Erick', 'Melissa', 'Rodrigo', 'Vanessa', 'Mauricio', 'Priscila', 'Hernán', 'Jenny',
        'Wilson', 'Tatiana', 'Víctor', 'Elizabeth',
    ];

    private array $secondNames = [
        'Alejandro', 'Isabel', 'Antonio', 'Lucía', 'Alberto', 'Elena', 'Daniel', 'Rosa',
        'Ramón', 'Beatriz', 'Alfredo', 'Pilar', 'Ernesto', 'Carmen', 'Arturo', 'Alicia',
        '', '', '', '', '', '', '', '', '', // vacíos para variedad
    ];

    private array $firstSurnames = [
        'García', 'Rodríguez', 'López', 'Martínez', 'González', 'Pérez', 'Sánchez',
        'Ramírez', 'Torres', 'Flores', 'Rivera', 'Morales', 'Jiménez', 'Herrera', 'Díaz',
        'Vargas', 'Cabrera', 'Castro', 'Suárez', 'Andrade', 'Vega', 'Reyes', 'Chávez',
        'Ríos', 'Mendoza', 'Guerrero', 'Ortega', 'Alvarado', 'Salazar', 'Espinoza',
        'Paredes', 'Benítez', 'Delgado', 'Rosero', 'Noboa', 'Játiva', 'Imbaquingo',
    ];

    private array $secondSurnames = [
        'Gutiérrez', 'Ruiz', 'Moreno', 'Molina', 'Silva', 'Romero', 'Serrano', 'Blanco',
        'Fuentes', 'Carrillo', 'Miranda', 'Navarro', 'Cruz', 'Cortés', 'Medina', 'Aguilar',
        'Peña', 'Vásquez', 'Acosta', 'Hidalgo', 'Ponce', 'Mena', 'Ayala', 'Toapanta',
        'Cárdenas', 'Villacís', 'Montesdeoca', 'Pozo', 'Revelo', 'Chamorro',
    ];

    private array $academicDegrees = [
        'PhD en Ciencias de la Computación',
        'Magíster en Ingeniería de Software',
        'Magíster en Telecomunicaciones',
        'PhD en Inteligencia Artificial',
        'Magíster en Administración de Empresas',
        'Magíster en Ciencias de la Educación',
        'PhD en Matemáticas Aplicadas',
        'Magíster en Redes y Comunicaciones',
        'Magíster en Gestión Financiera',
        'PhD en Diseño Gráfico y Multimedia',
    ];

    private int $personCounter = 10000000;

    public function run(): void
    {
        $institution = Institution::first();

        if (! $institution) {
            $this->command->error('No hay institución. Ejecuta InstitutionProductionSeeder primero.');
            return;
        }

        // ── Admins ────────────────────────────────────────────────────────────
        $this->createAdmin($institution, 'Rector', 'rector@utn.edu.ec', 'password');
        $this->createAdmin($institution, 'Vicerrector Académico', 'vicerrector@utn.edu.ec', 'password');
        $this->createAdmin($institution, 'Secretaria General', 'secretaria@utn.edu.ec', 'password');

        $this->command->info('✓ Admins creados: 3');

        // ── Docentes ─────────────────────────────────────────────────────────
        $teacherEmails = [];
        for ($i = 0; $i < 20; $i++) {
            $this->createTeacher($institution, $i, $teacherEmails);
        }

        $this->command->info('✓ Docentes creados: 20');

        // ── Estudiantes ───────────────────────────────────────────────────────
        $studentEmails = [];
        for ($i = 0; $i < 50; $i++) {
            $this->createStudent($i, $studentEmails);
        }

        $this->command->info('✓ Estudiantes creados: 50');
        $this->command->info('');
        $this->command->info('════════════════════════════════════');
        $this->command->info('  PeopleProductionSeeder DONE ✓    ');
        $this->command->info('════════════════════════════════════');
    }

    private function makePerson(string $gender = 'M'): Person
    {
        $maleFirst   = ['Carlos', 'José', 'Luis', 'Jorge', 'Roberto', 'Diego', 'Andrés',
                        'Ricardo', 'Sebastián', 'Miguel', 'Eduardo', 'Pablo', 'Iván',
                        'Francisco', 'Javier', 'Kevin', 'Bryan', 'Steven', 'Jonathan',
                        'Erick', 'Rodrigo', 'Mauricio', 'Hernán', 'Wilson', 'Víctor'];
        $femaleFirst = ['María', 'Ana', 'Patricia', 'Sandra', 'Verónica', 'Gabriela',
                        'Fernanda', 'Claudia', 'Valeria', 'Daniela', 'Natalia', 'Cristina',
                        'Lorena', 'Alexandra', 'Mónica', 'Samantha', 'Estefanía', 'Pamela',
                        'Karla', 'Melissa', 'Vanessa', 'Priscila', 'Jenny', 'Tatiana', 'Elizabeth'];

        $firstName   = $gender === 'M'
            ? $maleFirst[array_rand($maleFirst)]
            : $femaleFirst[array_rand($femaleFirst)];
        $secondName  = $this->secondNames[array_rand($this->secondNames)];
        $firstSurname  = $this->firstSurnames[array_rand($this->firstSurnames)];
        $secondSurname = $this->secondSurnames[array_rand($this->secondSurnames)];

        $this->personCounter++;

        return Person::create([
            'first_name'            => $firstName,
            'second_name'           => $secondName,
            'first_surname'         => $firstSurname,
            'second_surname'        => $secondSurname,
            'identification_number' => (string) $this->personCounter,
            'phone'                 => '06' . rand(2000000, 2999999),
            'cellphone'             => '09' . rand(10000000, 99999999),
            'birthdate'             => now()->subYears(rand(22, 55))->subDays(rand(0, 364)),
            'place_birth'           => collect(['Ibarra', 'Quito', 'Guayaquil', 'Cuenca', 'Ambato', 'Tulcán', 'Otavalo'])->random(),
            'main_street'           => 'Av. ' . collect(['17 de Julio', 'El Retorno', 'Teodoro Gómez', 'Pérez Guerrero', 'Cristóbal de Troya'])->random(),
            'secondary_street'      => 'Calle ' . rand(1, 30),
            'neighborhood'          => collect(['El Sagrario', 'San Francisco', 'Los Ceibos', 'La Florida', 'Alpachaca'])->random(),
            'marital_status_id'     => rand(1, 5),
            'type_identification_id'=> 1,
            'sex_id'                => $gender === 'M' ? 1 : 2,
            'nationality_id'        => 1,
            'education_level_id'    => rand(3, 5),
            'countries_id'          => 1,
            'provinces_id'          => rand(1, 24),
            'cities_id'             => rand(1, 50),
        ]);
    }

    private function createAdmin(Institution $institution, string $position, string $email, string $password): void
    {
        $gender = rand(0, 1) ? 'M' : 'F';
        $person = $this->makePerson($gender);

        $user = User::create([
            'name'              => $person->full_name,
            'email'             => $email,
            'password'          => Hash::make($password),
            'email_verified_at' => now(),
            'person_id'         => $person->id,
        ]);

        Admin::create([
            'person_id'      => $person->id,
            'institution_id' => $institution->id,
            'position'       => $position,
            'active'         => true,
        ]);

        // Asignar rol admin si existe
        if (method_exists($user, 'assignRole')) {
            try { $user->assignRole('admin'); } catch (\Exception $e) {}
        }
    }

    private function createTeacher(Institution $institution, int $index, array &$usedEmails): void
    {
        $gender = $index % 3 === 0 ? 'F' : 'M';
        $person = $this->makePerson($gender);

        $slug  = strtolower(
            $this->removeDiacritics($person->first_name) . '.' .
            $this->removeDiacritics($person->first_surname)
        );
        $email = $this->uniqueEmail("{$slug}@utn.edu.ec", $usedEmails);
        $usedEmails[] = $email;

        $user = User::create([
            'name'              => $person->full_name,
            'email'             => $email,
            'password'          => Hash::make('password'),
            'email_verified_at' => now(),
            'person_id'         => $person->id,
        ]);

        Teacher::create([
            'person_id'       => $person->id,
            'institution_id'  => $institution->id,
            'hire_date'       => now()->subYears(rand(1, 15))->subDays(rand(0, 364)),
            'academic_degree' => $this->academicDegrees[$index % count($this->academicDegrees)],
            'active'          => true,
        ]);

        if (method_exists($user, 'assignRole')) {
            try { $user->assignRole('docente'); } catch (\Exception $e) {}
        }
    }

    private function createStudent(int $index, array &$usedEmails): void
    {
        $gender = $index % 2 === 0 ? 'M' : 'F';
        $person = $this->makePerson($gender);

        $slug  = strtolower(
            $this->removeDiacritics($person->first_name) . '.' .
            $this->removeDiacritics($person->first_surname)
        );
        $email = $this->uniqueEmail("{$slug}@est.utn.edu.ec", $usedEmails);
        $usedEmails[] = $email;

        $year   = now()->year;
        $number = str_pad($index + 1, 4, '0', STR_PAD_LEFT);
        $enrollmentNumber = "UTN-{$year}-{$number}";

        $user = User::create([
            'name'              => $person->full_name,
            'email'             => $email,
            'password'          => Hash::make('password'),
            'email_verified_at' => now(),
            'person_id'         => $person->id,
        ]);

        Student::create([
            'person_id'         => $person->id,
            'enrollment_number' => $enrollmentNumber,
            'active'            => true,
        ]);

        if (method_exists($user, 'assignRole')) {
            try { $user->assignRole('estudiante'); } catch (\Exception $e) {}
        }
    }

    private function uniqueEmail(string $base, array $used): string
    {
        if (! in_array($base, $used) && ! User::where('email', $base)->exists()) {
            return $base;
        }
        $parts = explode('@', $base);
        $i = 2;
        do {
            $candidate = $parts[0] . $i . '@' . $parts[1];
            $i++;
        } while (in_array($candidate, $used) || User::where('email', $candidate)->exists());

        return $candidate;
    }

    private function removeDiacritics(string $str): string
    {
        return strtr($str, [
            'á'=>'a','é'=>'e','í'=>'i','ó'=>'o','ú'=>'u',
            'Á'=>'a','É'=>'e','Í'=>'i','Ó'=>'o','Ú'=>'u',
            'ñ'=>'n','Ñ'=>'n','ü'=>'u','Ü'=>'u',
        ]);
    }
}