<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class ProductionSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
            // 1. Institucion
            \Modules\Institucion\database\seeders\InstitutionProductionSeeder::class,

            // 2. Personas, usuarios, docentes, estudiantes, admins
            \Modules\People\database\seeders\PeopleProductionSeeder::class,

            // 3. Períodos académicos + parámetros de evaluación
            \Modules\Academic\database\seeders\AcademicPeriodProductionSeeder::class,

            // 4. Aulas
            \Modules\Institucion\database\seeders\ClassroomProductionSeeder::class,

            // 5. Matrículas, secciones, horarios, notas
            \Modules\Academic\database\seeders\EnrollmentProductionSeeder::class,
        ]);
    }
}