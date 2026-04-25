<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Nationality;

class NationalitySeeder extends Seeder
{
    public function run(): void
    {
        Nationality::firstOrCreate(
            [
                'country_id' => 1,
                'name' => 'Ecuatoriana',
            ],
            [
                'active' => true,
            ]
        );
    }
}