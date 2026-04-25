<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\MaritalStatus;

class MaritalStatusSeeder extends Seeder
{
    public function run(): void
    {
        $statuses = [
            'Soltero/a',
            'Casado/a',
            'Divorciado/a',
            'Viudo/a',
            'Unión libre',
            'Separado/a',
        ];

        foreach ($statuses as $status) {
            MaritalStatus::firstOrCreate(
                ['name' => $status],
                ['active' => true]
            );
        }
    }
}