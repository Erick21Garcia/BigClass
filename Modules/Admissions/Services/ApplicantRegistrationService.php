<?php

namespace Modules\Admissions\Services;

use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Modules\Admissions\Models\Applicant;

class ApplicantRegistrationService
{
    public function __construct(
        private readonly ApplicantEmailVerificationService $verificationService
    ) {}

    /**
     * Registro inicial. Solo lo mínimo para tener acceso
     * (User + Applicant en 'borrador'). Los datos completos de Person
     * se llenan después, ya logueado.
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

        $applicant = Applicant::create([
            'user_id'          => $user->id,
            'career_id'        => $data['career_id'],
            'status'           => 'borrador',
            'last_activity_at' => now(),
        ]);

        $this->verificationService->send($user);

        return $applicant;
    }
}