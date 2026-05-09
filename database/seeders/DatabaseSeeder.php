<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
            // ── Base del sistema ──────────────────────────────────────────────
            RolePermissionSeeder::class,
            LocationSeeder::class,
            MaritalStatusSeeder::class,
            TypeIdentificationSeeder::class,
            SexSeeder::class,
            NationalitySeeder::class,
            EducationLevelSeeder::class,

            // ── Usuario admin base del sistema ────────────────────────────────
            UserSeeder::class,

            // ── Datos de producción simulados ─────────────────────────────────
            ProductionSeeder::class,
        ]);
    }
}