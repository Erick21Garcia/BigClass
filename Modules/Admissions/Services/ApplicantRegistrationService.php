<?php

namespace Modules\Admissions\Services;

use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Modules\Admissions\Models\Applicant;

class ApplicantRegistrationService
{
    /**
     * Registro inicial — Punto 1. Solo lo mínimo para tener acceso
     * (User + Applicant en 'borrador'). Los datos completos de Person
     * (Punto 2) se llenan después, ya logueado.
     */
    public function register(array $data): Applicant
    {
        $user = User::create([
            'name'     => $data['name'],
            'email'    => $data['email'],
            'password' => Hash::make($data['password']),
        ]);

        // Requiere que el rol 'aspirante' ya exista (seed de roles del
        // sistema) — igual que 'admin'/'docente'/'estudiante'.
        $user->assignRole('aspirante');

        return Applicant::create([
            'user_id'          => $user->id,
            'career_id'        => $data['career_id'],
            'status'           => 'borrador',
            'last_activity_at' => now(),
        ]);
    }
}