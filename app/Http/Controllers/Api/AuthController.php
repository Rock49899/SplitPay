<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;

use App\Http\Requests\LoginRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use App\Models\User;

class AuthController extends Controller
{
    public function login(LoginRequest $request)
    {
        // dump('Tentative de connexion pour: ' . $request->email);

        $user = User::where('email', $request->email)->first();

        if (!$user) {
            // dump('Utilisateur non trouve');
            return response()->json([
                'message' => 'Email ou mot de passe incorrect'
            ], 401);
        }

        if (!Hash::check($request->password, $user->password)) {
            // dump('Mot de passe incorrect');
            return response()->json([
                'message' => 'Email ou mot de passe incorrect'
            ], 401);
        }

        if (!$user->is_active) {
            // dump('Compte desactive');
            return response()->json([
                'message' => 'Votre compte est desactive. Contactez l\'administrateur.'
            ], 403);
        }

        $activeAnnexes = $user->annexes->filter(fn($annexe) => $annexe->is_active);
        
        if ($activeAnnexes->isEmpty()) {
            // dump('Aucune annexe active pour cet utilisateur');
            return response()->json([
                'message' => 'Aucune annexe active. Contactez l\'administrateur.'
            ], 403);
        }

        $token = $user->createToken('auth-token')->plainTextToken;

        return response()->json([
            'message' => 'Connexion reussie',
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'scope' => $user->scope,
                'annexes' => $activeAnnexes->map(function($annexe) {
                    return [
                        'id' => $annexe->id,
                        'name' => $annexe->name,
                        'is_principal' => $annexe->pivot->is_principal,
                    ];
                }),
            ],
            'token' => $token,
        ], 200);
    }

    public function logout(Request $request)
    {
        $user = $request->user();
        
        // dump('Deconnexion de: ' . $user->email);

        $user->tokens()->delete();

        return response()->json([
            'message' => 'Deconnexion reussie'
        ], 200);
    }

    public function me(Request $request)
    {
        $user = $request->user();

        // dump('Recuperation des infos pour: ' . $user->email);

        $user->load(['annexes' => function($query) {
            $query->where('is_active', true);
        }]);

        $roles = collect();
        $permissionsByAnnexe = []; // Permissions groupées par annexe
        
        foreach ($user->annexes as $annexe) {
            $role = \App\Models\Role::with('permissions')->find($annexe->pivot->role_id);
            if ($role) {
                $roles->push([
                    'code' => $role->code,
                    'label' => $role->label,
                    'annexe' => $annexe->name,
                    'annexe_id' => $annexe->id,
                ]);
                
                // Stocker les permissions par annexe (pas de fusion)
                $permissionsByAnnexe[$annexe->id] = $role->permissions->map(function($perm) {
                    return [
                        'code' => $perm->code,
                        'label' => $perm->label,
                        'module' => $perm->module,
                    ];
                })->toArray();
            }
        }

        return response()->json([
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'phone' => $user->phone,
                'bio' => $user->bio,
                'avatar' => $user->avatar,
                'avatar_url' => $user->avatar_url,
                'scope' => $user->scope,
                'is_active' => $user->is_active,
                'annexes' => $user->annexes->map(function($annexe) {
                    return [
                        'id' => $annexe->id,
                        'name' => $annexe->name,
                        'is_principal' => $annexe->pivot->is_principal,
                    ];
                }),
                'roles' => $roles,
                'permissions_by_annexe' => $permissionsByAnnexe, // Permissions groupées par annexe
            ],
        ], 200);
    }

    /**
     * Récupère les informations de l'utilisateur pour une annexe spécifique
     * Route: GET /admin/me/annexe/{annexeId}
     */
    public function meForAnnexe(Request $request, $annexeId)
    {
        $user = $request->user();
        
        // Vérifier que l'utilisateur a accès à cette annexe
        $user->load(['annexes' => function($query) use ($annexeId) {
            $query->where('annexes.id', $annexeId)
                  ->where('is_active', true);
        }]);
        
        $annexe = $user->annexes->first();
        
        if (!$annexe) {
            return response()->json([
                'message' => 'Vous n\'avez pas accès à cette annexe'
            ], 403);
        }
        
        $role = \App\Models\Role::with('permissions')->find($annexe->pivot->role_id);
        
        $permissions = [];
        if ($role) {
            $permissions = $role->permissions->pluck('code')->toArray();
        }
        
        return response()->json([
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'annexe_id' => $annexe->id,
                'scope' => $user->scope,
            ],
            'annexe' => [
                'id' => $annexe->id,
                'name' => $annexe->name,
            ],
            'role' => $role ? [
                'code' => $role->code,
                'label' => $role->label,
            ] : null,
            'permissions' => $permissions,
        ], 200);
    }
}
