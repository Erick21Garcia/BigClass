<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class ProductionSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
            // 1. Institución
            \Modules\Institucion\Database\Seeders\InstitutionProductionSeeder::class,

            // 2. Personas, usuarios, docentes, estudiantes, admins
            \Modules\People\Database\Seeders\PeopleProductionSeeder::class,

            // 3. Períodos académicos + parámetros de evaluación
            \Modules\Academic\Database\Seeders\AcademicPeriodProductionSeeder::class,

            // 4. Aulas
            \Modules\Institucion\Database\Seeders\ClassroomProductionSeeder::class,

            // 5. Matrículas, secciones, horarios, notas
            \Modules\Academic\Database\Seeders\EnrollmentProductionSeeder::class,
        ]);
    }
}