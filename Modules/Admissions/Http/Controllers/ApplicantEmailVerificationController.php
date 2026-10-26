<?php

namespace Modules\Admissions\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Modules\Admissions\Services\ApplicantEmailVerificationService;

class ApplicantEmailVerificationController extends Controller
{
    public function __construct(
        private readonly ApplicantEmailVerificationService $verificationService
    ) {}

    /**
     * Ruta firmada: la seguridad la da la firma de la URL, así que funciona
     * aunque el aspirante abra el enlace desde otro dispositivo.
     */
    public function verify(User $user, string $hash): RedirectResponse
    {
        try {
            $this->verificationService->verify($user, $hash);
        } catch (\RuntimeException $e) {
            abort(403, $e->getMessage());
        }

        return redirect()
            ->route('admissions.applicant.dashboard')
            ->with('success', 'Correo verificado correctamente.');
    }

    public function resend(Request $request): RedirectResponse
    {
        $user = $request->user();

        if ($user->hasVerifiedEmail()) {
            return back();
        }

        $this->verificationService->send($user);

        return back()->with('success', 'Te enviamos un nuevo enlace de verificación.');
    }
}