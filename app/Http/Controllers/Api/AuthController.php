<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;

use App\Http\Requests\LoginRequest;
use App\Http\Requests\UserRequestOtpRequest;
use App\Http\Requests\UserVerifyOtpRequest;
use App\Mail\UserOtpMail;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use App\Models\User;
use Carbon\Carbon;

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

        if (empty($request->password)) {
            return response()->json([
                'message' => 'Mot de passe requis pour cette méthode. Utilisez la connexion par OTP sinon.'
            ], 422);
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

        $institutionId = $user->annexe?->institution_id;
        $activeAnnexes = $user->annexes
            ->filter(fn ($annexe) => $annexe->is_active)
            ->when($institutionId, fn ($collection) => $collection->where('institution_id', $institutionId));
        
        if ($activeAnnexes->isEmpty()) {
            // dump('Aucune annexe active pour cet utilisateur');
            return response()->json([
                'message' => 'Aucune annexe active. Contactez l\'administrateur.'
            ], 403);
        }

        [$token, $expiresAt] = $this->issueAuthToken($user);

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
            'expires_at' => $expiresAt->toIso8601String(),
            'expires_in_minutes' => $this->tokenTtlMinutes(),
        ], 200);
    }

    public function requestOtp(UserRequestOtpRequest $request)
    {
        $email = strtolower(trim($request->validated()['email']));

        $user = User::where('email', $email)->first();
        if (! $user) {
            return response()->json(['message' => 'Aucun compte trouvé pour cet email'], 404);
        }

        if (! $user->is_active) {
            return response()->json(['message' => 'Votre compte est désactivé. Contactez un administrateur.'], 403);
        }

        $institutionId = $user->annexe?->institution_id;
        $activeAnnexes = $user->annexes
            ->filter(fn ($annexe) => $annexe->is_active)
            ->when($institutionId, fn ($collection) => $collection->where('institution_id', $institutionId));
        if ($activeAnnexes->isEmpty()) {
            return response()->json(['message' => 'Aucune annexe active pour ce compte.'], 403);
        }

        $otp = str_pad((string) random_int(0, 999999), 6, '0', STR_PAD_LEFT);
        Cache::put("user_otp_{$user->id}", $otp, now()->addMinutes(10));

        Mail::to($user->email)->send(new UserOtpMail($otp, $user));

        return response()->json([
            'message' => 'Code OTP envoyé',
            'expires_in_minutes' => 10,
        ], 202);
    }

    public function verifyOtp(UserVerifyOtpRequest $request)
    {
        $data = $request->validated();
        $email = strtolower(trim($data['email']));
        $otp = (string) $data['otp'];

        $user = User::where('email', $email)->first();
        if (! $user) {
            return response()->json(['message' => 'Aucun compte trouvé pour cet email'], 404);
        }

        if (! $user->is_active) {
            return response()->json(['message' => 'Votre compte est désactivé. Contactez un administrateur.'], 403);
        }

        $cached = Cache::get("user_otp_{$user->id}");
        if (! $cached || ! hash_equals((string) $cached, $otp)) {
            return response()->json(['message' => 'OTP invalide ou expiré'], 401);
        }

        Cache::forget("user_otp_{$user->id}");

        $institutionId = $user->annexe?->institution_id;
        $activeAnnexes = $user->annexes
            ->filter(fn ($annexe) => $annexe->is_active)
            ->when($institutionId, fn ($collection) => $collection->where('institution_id', $institutionId));
        if ($activeAnnexes->isEmpty()) {
            return response()->json(['message' => 'Aucune annexe active pour ce compte.'], 403);
        }

        [$token, $expiresAt] = $this->issueAuthToken($user);

        return response()->json([
            'message' => 'Connexion OTP réussie',
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'scope' => $user->scope,
                'annexes' => $activeAnnexes->map(function ($annexe) {
                    return [
                        'id' => $annexe->id,
                        'name' => $annexe->name,
                        'is_principal' => $annexe->pivot->is_principal,
                    ];
                }),
            ],
            'token' => $token,
            'expires_at' => $expiresAt->toIso8601String(),
            'expires_in_minutes' => $this->tokenTtlMinutes(),
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

        $institutionId = $user->annexe?->institution_id;
        $user->load(['annexes' => function($query) use ($institutionId) {
            $query->where('is_active', true);
            if ($institutionId) {
                $query->where('institution_id', $institutionId);
            }
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

    private function tokenTtlMinutes(): int
    {
        return (int) (config('sanctum.expiration') ?: 720);
    }

    /**
     * @return array{0:string,1:\Carbon\Carbon}
     */
    private function issueAuthToken(User $user): array
    {
        $expiresAt = Carbon::now()->addMinutes($this->tokenTtlMinutes());
        $token = $user->createToken('auth-token', ['*'], $expiresAt)->plainTextToken;

        return [$token, $expiresAt];
    }
}
