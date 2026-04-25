<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Sex;

class SexSeeder extends Seeder
{
    public function run(): void
    {
        $sexes = [
            'Masculino',
            'Femenino',
            'Prefiere no especificar',
        ];

        foreach ($sexes as $sex) {
            Sex::firstOrCreate(
                ['name' => $sex],
                ['active' => true]
            );
        }
    }
}