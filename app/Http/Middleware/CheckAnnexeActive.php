<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckAnnexeActive
{
   
    public function handle(Request $request, Closure $next): Response
    {
        // Verifier si un utilisateur est connecte
        if (!auth()->check()) {
            return response()->json([
                'message' => 'Non authentifie'
            ], 401);
        }

        $user = auth()->user();

        if (method_exists($user, 'isPlatformAdmin') && $user->isPlatformAdmin()) {
            return $next($request);
        }

        if (method_exists($user, 'isAccountActive') && ! $user->isAccountActive()) {
            return response()->json([
                'message' => 'Votre compte est actuellement bloqué. Contactez l\'administrateur.'
            ], 403);
        }
        
        // Recuperer toutes les annexes accessibles par l'utilisateur
        $annexes = $user->annexes;
        
        if ($annexes->isEmpty()) {
            dump("User {$user->email} n'a aucune annexe assignee");
            return response()->json([
                'message' => 'Aucune annexe assignee. Contactez l\'administrateur.'
            ], 403);
        }

        // Verifier si AU MOINS UNE annexe est active
        $activeAnnexes = $annexes->filter(function($annexe) {
            return $annexe->is_active;
        });

        if ($activeAnnexes->isEmpty()) {
            dump("Toutes les annexes de {$user->email} sont desactivees");
            $annexeNames = $annexes->pluck('name')->implode(', ');
            return response()->json([
                'message' => 'Toutes vos annexes sont actuellement desactivees. Contactez l\'administrateur.',
                'annexes' => $annexeNames
            ], 403);
        }

        dump("User {$user->email} a acces a " . $activeAnnexes->count() . " annexe(s) active(s)");
        
        // Continuer vers le controller
        return $next($request);
    }
}
