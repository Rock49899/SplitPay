<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckPermission
{
    
    public function handle(Request $request, Closure $next, string $permission): Response
    {
        // Verifier si un utilisateur est connecte
        if (!auth()->check()) {
            return response()->json([
                'message' => 'Non authentifie'
            ], 401);
        }

        $user = auth()->user();

        // Recuperer toutes les permissions de l'utilisateur via ses roles
        $userPermissions = collect();
        
        // Pour chaque annexe ou l'utilisateur a un role
        foreach ($user->annexes as $annexe) {
            $roleId = $annexe->pivot->role_id;
            
            // Recuperer le role
            $role = \App\Models\Role::find($roleId);
            
            if ($role) {
                // Ajouter les permissions de ce role
                $userPermissions = $userPermissions->merge($role->permissions);
            }
        }

        // Verifier si la permission existe dans la collection
        $hasPermission = $userPermissions->contains('code', $permission);
        
        if (!$hasPermission) {
            return response()->json([
                'message' => 'Acces refuse. Permission manquante: ' . $permission
            ], 403);
        }
        
        // Continuer vers le controller
        return $next($request);
    }
}
