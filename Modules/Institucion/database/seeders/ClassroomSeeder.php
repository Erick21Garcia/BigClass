<?php

namespace Modules\Institucion\Database\Seeders;

use Illuminate\Database\Seeder;
use Modules\Institucion\Models\Classroom;

class ClassroomSeeder extends Seeder
{
    public function run(): void
    {
        $classrooms = [
            // Aulas teóricas
            ['name' => 'Aula 101', 'code' => 'A101', 'capacity' => 40, 'type' => 'theory'],
            ['name' => 'Aula 102', 'code' => 'A102', 'capacity' => 40, 'type' => 'theory'],
            ['name' => 'Aula 103', 'code' => 'A103', 'capacity' => 35, 'type' => 'theory'],
            ['name' => 'Aula 201', 'code' => 'A201', 'capacity' => 45, 'type' => 'theory'],
            ['name' => 'Aula 202', 'code' => 'A202', 'capacity' => 45, 'type' => 'theory'],
            ['name' => 'Aula 203', 'code' => 'A203', 'capacity' => 30, 'type' => 'theory'],
            ['name' => 'Aula 301', 'code' => 'A301', 'capacity' => 50, 'type' => 'theory'],
            ['name' => 'Aula 302', 'code' => 'A302', 'capacity' => 50, 'type' => 'theory'],

            // Laboratorios
            ['name' => 'Laboratorio de Computación 1', 'code' => 'LC1', 'capacity' => 30, 'type' => 'lab'],
            ['name' => 'Laboratorio de Computación 2', 'code' => 'LC2', 'capacity' => 30, 'type' => 'lab'],
            ['name' => 'Laboratorio de Redes',          'code' => 'LR1', 'capacity' => 25, 'type' => 'lab'],
            ['name' => 'Laboratorio de Electrónica',    'code' => 'LE1', 'capacity' => 20, 'type' => 'lab'],
            ['name' => 'Laboratorio de Física',         'code' => 'LF1', 'capacity' => 25, 'type' => 'lab'],
            ['name' => 'Laboratorio de Química',        'code' => 'LQ1', 'capacity' => 25, 'type' => 'lab'],

            // Virtuales
            ['name' => 'Aula Virtual 1', 'code' => 'AV1', 'capacity' => 100, 'type' => 'virtual'],
            ['name' => 'Aula Virtual 2', 'code' => 'AV2', 'capacity' => 100, 'type' => 'virtual'],
        ];

        foreach ($classrooms as $classroom) {
            Classroom::firstOrCreate(
                ['code' => $classroom['code']],
                array_merge($classroom, ['active' => true])
            );
        }

        $this->command->info('✓ Aulas creadas: ' . count($classrooms));
    }
}