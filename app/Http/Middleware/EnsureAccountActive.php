<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureAccountActive
{
    public function handle(Request $request, Closure $next): Response
    {
        if (! auth()->check()) {
            return response()->json(['message' => 'Non authentifié'], 401);
        }

        $user = $request->user();

        if (! $user || (method_exists($user, 'isAccountActive') && ! $user->isAccountActive())) {
            return response()->json([
                'message' => 'Votre compte, votre annexe ou votre institution est désactivé.'
            ], 403);
        }

        return $next($request);
    }
}
