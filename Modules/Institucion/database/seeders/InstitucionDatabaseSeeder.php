<?php

namespace Modules\Institucion\Database\Seeders;

use Illuminate\Database\Seeder;

class InstitucionDatabaseSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $this->call([
            InstitutionSeeder::class,
            SubjectSeeder::class
        ]);
    }
}
