<?php

namespace Modules\Institucion\Database\Seeders;

use Illuminate\Database\Seeder;
use Modules\Institucion\Models\Classroom;

class ClassroomProductionSeeder extends Seeder
{
    public function run(): void
    {
        $classrooms = [
            // Aulas teóricas FICA
            ['name' => 'Aula FICA 101', 'code' => 'FICA-101', 'capacity' => 40, 'type' => 'theory'],
            ['name' => 'Aula FICA 102', 'code' => 'FICA-102', 'capacity' => 40, 'type' => 'theory'],
            ['name' => 'Aula FICA 201', 'code' => 'FICA-201', 'capacity' => 45, 'type' => 'theory'],
            ['name' => 'Aula FICA 202', 'code' => 'FICA-202', 'capacity' => 35, 'type' => 'theory'],
            // Laboratorios FICA
            ['name' => 'Laboratorio de Computación 1', 'code' => 'LAB-COMP-1', 'capacity' => 30, 'type' => 'lab'],
            ['name' => 'Laboratorio de Computación 2', 'code' => 'LAB-COMP-2', 'capacity' => 30, 'type' => 'lab'],
            ['name' => 'Laboratorio de Redes',          'code' => 'LAB-RED-1',  'capacity' => 25, 'type' => 'lab'],
            ['name' => 'Laboratorio de Electrónica',    'code' => 'LAB-ELE-1',  'capacity' => 25, 'type' => 'lab'],
            // Aulas teóricas FACAE
            ['name' => 'Aula FACAE 101', 'code' => 'FACAE-101', 'capacity' => 40, 'type' => 'theory'],
            ['name' => 'Aula FACAE 102', 'code' => 'FACAE-102', 'capacity' => 40, 'type' => 'theory'],
            ['name' => 'Aula FACAE 201', 'code' => 'FACAE-201', 'capacity' => 35, 'type' => 'theory'],
            // Aulas teóricas FECYT
            ['name' => 'Aula FECYT 101', 'code' => 'FECYT-101', 'capacity' => 40, 'type' => 'theory'],
            ['name' => 'Aula FECYT 102', 'code' => 'FECYT-102', 'capacity' => 35, 'type' => 'theory'],
            ['name' => 'Laboratorio de Diseño',         'code' => 'LAB-DIS-1',  'capacity' => 25, 'type' => 'lab'],
            // Virtuales
            ['name' => 'Aula Virtual 1', 'code' => 'AV-001', 'capacity' => 100, 'type' => 'virtual'],
            ['name' => 'Aula Virtual 2', 'code' => 'AV-002', 'capacity' => 100, 'type' => 'virtual'],
            // Auditorio
            ['name' => 'Auditorio General', 'code' => 'AUD-001', 'capacity' => 200, 'type' => 'theory'],
        ];

        foreach ($classrooms as $data) {
            Classroom::firstOrCreate(
                ['code' => $data['code']],
                array_merge($data, ['active' => true])
            );
        }

        $this->command->info('✓ Aulas creadas: ' . count($classrooms));
    }
}