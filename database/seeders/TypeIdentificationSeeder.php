<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\TypeIdentification;

class TypeIdentificationSeeder extends Seeder
{
    public function run(): void
    {
        $types = [
            'Cédula',
            'Pasaporte',
            'RUC',
            'DNI',
            'Carnet de refugiado',
            'Identificación extranjera',
        ];

        foreach ($types as $type) {
            TypeIdentification::firstOrCreate(
                ['name' => $type],
                ['active' => true]
            );
        }
    }
}