<?php

namespace Modules\Institucion\Database\Seeders;

use Illuminate\Database\Seeder;
use Modules\Institucion\Models\Subject;
use Illuminate\Support\Facades\DB;

class SubjectSeeder extends Seeder
{
    public function run(): void
    {
        // ── Primer semestre ───────────────────────────────────────────────────
        $matematicasI = Subject::create([
            'code'        => 'MAT-101',
            'name'        => 'Matemáticas I',
            'description' => 'Fundamentos de álgebra, funciones y trigonometría.',
            'credits'     => 4,
            'active'      => true,
        ]);

        $programacionI = Subject::create([
            'code'        => 'PRG-101',
            'name'        => 'Programación I',
            'description' => 'Introducción a la programación estructurada y algorítmica.',
            'credits'     => 4,
            'active'      => true,
        ]);

        $fisicaI = Subject::create([
            'code'        => 'FIS-101',
            'name'        => 'Física I',
            'description' => 'Mecánica clásica, cinemática y dinámica.',
            'credits'     => 4,
            'active'      => true,
        ]);

        $introduccionSistemas = Subject::create([
            'code'        => 'SIS-101',
            'name'        => 'Introducción a los Sistemas',
            'description' => 'Conceptos generales de sistemas de información y tecnología.',
            'credits'     => 3,
            'active'      => true,
        ]);

        $comunicacion = Subject::create([
            'code'        => 'COM-101',
            'name'        => 'Comunicación y Redacción',
            'description' => 'Técnicas de comunicación oral y escrita en contextos académicos.',
            'credits'     => 3,
            'active'      => true,
        ]);

        // ── Segundo semestre ──────────────────────────────────────────────────
        $matematicasII = Subject::create([
            'code'        => 'MAT-201',
            'name'        => 'Matemáticas II',
            'description' => 'Cálculo diferencial e integral de una variable.',
            'credits'     => 4,
            'active'      => true,
        ]);

        $programacionII = Subject::create([
            'code'        => 'PRG-201',
            'name'        => 'Programación II',
            'description' => 'Programación orientada a objetos y estructuras de datos básicas.',
            'credits'     => 4,
            'active'      => true,
        ]);

        $fisicaII = Subject::create([
            'code'        => 'FIS-201',
            'name'        => 'Física II',
            'description' => 'Electromagnetismo, ondas y óptica.',
            'credits'     => 4,
            'active'      => true,
        ]);

        $baseDatos = Subject::create([
            'code'        => 'BD-201',
            'name'        => 'Base de Datos I',
            'description' => 'Modelado relacional, SQL y diseño de bases de datos.',
            'credits'     => 3,
            'active'      => true,
        ]);

        $estadistica = Subject::create([
            'code'        => 'EST-201',
            'name'        => 'Estadística y Probabilidad',
            'description' => 'Estadística descriptiva, distribuciones y probabilidad aplicada.',
            'credits'     => 3,
            'active'      => true,
        ]);

        // ── Prerequisitos ─────────────────────────────────────────────────────
        // Matemáticas II requiere Matemáticas I
        DB::table('subject_prerequisites')->insert([
            'subject_id'      => $matematicasII->id,
            'prerequisite_id' => $matematicasI->id,
            'active'          => true,
        ]);

        // Programación II requiere Programación I
        DB::table('subject_prerequisites')->insert([
            'subject_id'      => $programacionII->id,
            'prerequisite_id' => $programacionI->id,
            'active'          => true,
        ]);

        // Física II requiere Física I y Matemáticas I
        DB::table('subject_prerequisites')->insert([
            'subject_id'      => $fisicaII->id,
            'prerequisite_id' => $fisicaI->id,
            'active'          => true,
        ]);
        DB::table('subject_prerequisites')->insert([
            'subject_id'      => $fisicaII->id,
            'prerequisite_id' => $matematicasI->id,
            'active'          => true,
        ]);

        // Base de Datos I requiere Programación I e Introducción a los Sistemas
        DB::table('subject_prerequisites')->insert([
            'subject_id'      => $baseDatos->id,
            'prerequisite_id' => $programacionI->id,
            'active'          => true,
        ]);
        DB::table('subject_prerequisites')->insert([
            'subject_id'      => $baseDatos->id,
            'prerequisite_id' => $introduccionSistemas->id,
            'active'          => true,
        ]);

        // Estadística requiere Matemáticas I
        DB::table('subject_prerequisites')->insert([
            'subject_id'      => $estadistica->id,
            'prerequisite_id' => $matematicasI->id,
            'active'          => true,
        ]);
    }
}