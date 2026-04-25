<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        // Crear usuario admin
        $admin = User::create([
            'name' => 'Admin User',
            'email' => 'admin@example.com',
            'password' => Hash::make('password'),
        ]);
        $admin->assignRole('admin');

        // Crear usuario docente
        $editor = User::create([
            'name' => 'Docente User',
            'email' => 'docente@example.com',
            'password' => Hash::make('password'),
        ]);
        $editor->assignRole('docente');

        // Crear usuario estudiante
        $user = User::create([
            'name' => 'Estudiante User',
            'email' => 'estudiante@example.com',
            'password' => Hash::make('password'),
        ]);
        $user->assignRole('estudiante');
    }
}
