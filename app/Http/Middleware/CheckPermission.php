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
            dump('Pas d\'utilisateur connecte');
            return response()->json([
                'message' => 'Non authentifie'
            ], 401);
        }

        $user = auth()->user();
        
        dump("Verification permission: {$permission} pour user: {$user->email}");

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
            dump("Permission refusee pour: {$user->email}");
            return response()->json([
                'message' => 'Acces refuse. Permission manquante: ' . $permission
            ], 403);
        }

        dump("Permission accordee: {$permission}");
        
        // Continuer vers le controller
        return $next($request);
    }
}
