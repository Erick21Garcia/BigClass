<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\EducationLevel;

class EducationLevelSeeder extends Seeder
{
    public function run(): void
    {
        $levels = [
            'Sin instrucción',
            'Educación inicial',
            'Educación básica',
            'Bachillerato',
            'Técnico / Tecnológico',
            'Universitario',
            'Maestría',
            'Doctorado',
        ];

        foreach ($levels as $level) {
            EducationLevel::firstOrCreate(
                ['name' => $level],
                ['active' => true]
            );
        }
    }
}