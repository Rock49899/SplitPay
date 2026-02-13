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
        foreach ($user->annexes as $annexe) {
            $role = \App\Models\Role::find($annexe->pivot->role_id);
            if ($role) {
                $roles->push([
                    'code' => $role->code,
                    'label' => $role->label,
                    'annexe' => $annexe->name,
                ]);
            }
        }

        return response()->json([
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'phone' => $user->phone,
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
            ],
        ], 200);
    }
}
