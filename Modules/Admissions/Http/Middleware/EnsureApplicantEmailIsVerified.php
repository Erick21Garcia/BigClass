<?php

namespace Modules\Admissions\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureApplicantEmailIsVerified
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if ($user && ! $user->hasVerifiedEmail()) {
            return back()->withErrors([
                'verification' => 'Verifica tu correo electrónico para continuar.',
            ]);
        }

        return $next($request);
    }
}