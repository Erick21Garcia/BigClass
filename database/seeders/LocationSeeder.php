<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Country;
use App\Models\Province;
use App\Models\City;

class LocationSeeder extends Seeder
{
    public function run(): void
    {
        $ecuador = Country::create([
            'name' => 'Ecuador',
            'active' => true,
        ]);

        $provinces = [
            'Azuay' => ['Cuenca', 'Gualaceo', 'Paute'],
            'Bolívar' => ['Guaranda', 'San Miguel', 'Chillanes'],
            'Cañar' => ['Azogues', 'La Troncal', 'Biblián'],
            'Carchi' => ['Tulcán', 'Montúfar', 'Mira'],
            'Chimborazo' => ['Riobamba', 'Guano', 'Alausí'],
            'Cotopaxi' => ['Latacunga', 'La Maná', 'Salcedo'],
            'El Oro' => ['Machala', 'Pasaje', 'Santa Rosa'],
            'Esmeraldas' => ['Esmeraldas', 'Atacames', 'Quinindé'],
            'Galápagos' => ['Puerto Baquerizo Moreno', 'Puerto Ayora', 'Puerto Villamil'],
            'Guayas' => ['Guayaquil', 'Durán', 'Samborondón'],
            'Imbabura' => ['Ibarra', 'Otavalo', 'Cotacachi'],
            'Loja' => ['Loja', 'Catamayo', 'Saraguro'],
            'Los Ríos' => ['Babahoyo', 'Quevedo', 'Ventanas'],
            'Manabí' => ['Portoviejo', 'Manta', 'Chone'],
            'Morona Santiago' => ['Macas', 'Sucúa', 'Gualaquiza'],
            'Napo' => ['Tena', 'Archidona', 'El Chaco'],
            'Orellana' => ['Francisco de Orellana', 'La Joya de los Sachas', 'Loreto'],
            'Pastaza' => ['Puyo', 'Mera', 'Santa Clara'],
            'Pichincha' => ['Quito', 'Cayambe', 'Rumiñahui'],
            'Santa Elena' => ['Santa Elena', 'La Libertad', 'Salinas'],
            'Santo Domingo de los Tsáchilas' => ['Santo Domingo', 'La Concordia', 'Valle Hermoso'],
            'Sucumbíos' => ['Nueva Loja', 'Shushufindi', 'Cascales'],
            'Tungurahua' => ['Ambato', 'Baños', 'Pelileo'],
            'Zamora Chinchipe' => ['Zamora', 'Yantzaza', 'El Pangui'],
        ];

        foreach ($provinces as $provinceName => $cities) {

            $province = Province::create([
                'country_id' => $ecuador->id,
                'name' => $provinceName,
                'active' => true,
            ]);

            foreach ($cities as $cityName) {
                City::create([
                    'province_id' => $province->id,
                    'name' => $cityName,
                    'active' => true,
                ]);
            }
        }
    }
}