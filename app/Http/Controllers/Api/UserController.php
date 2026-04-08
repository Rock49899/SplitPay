<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Traits\FiltersByAnnexe;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use App\Models\User;
use App\Models\Role;
use App\Http\Requests\StoreUserRequest;
use App\Http\Requests\UpdateUserRequest;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Illuminate\Database\QueryException;

class UserController extends Controller
{
    use FiltersByAnnexe;
    
    public function __construct()
    {
        // protéger ces routes : requiert authentification via Sanctum
        $this->middleware('auth:sanctum');
        // NOTE: appliquer une policy/middleware pour restreindre l'accès selon les rôles (ex: super_admin, gestionnaire)
    }

    public function index(Request $request)
    {
        $perPage = (int) $request->get('per_page', 15);

        $query = User::query();
        $institutionId = $this->getCurrentInstitutionId();
        $platformAdmin = method_exists(auth()->user(), 'isPlatformAdmin') && auth()->user()->isPlatformAdmin();

        if (!$institutionId && ! $platformAdmin) {
            return response()->json(['data' => [], 'total' => 0], 200);
        }

        // IMPORTANT: Ne montrer que les utilisateurs rattachés à l'institution courante, sauf pour l'admin plateforme
        if (! $platformAdmin) {
            $query->whereHas('annexes', fn ($q) => $q->where('annexes.institution_id', $institutionId));
        }

        if (!$this->isSuperAdminInstitution() && ! $platformAdmin) {
            $query->where('scope', '!=', 'institution');
        }

        try {
            // read raw search and ignore empty strings
            $raw = $request->get('search') ?? $request->get('q');
            $search = (is_string($raw) && strlen(trim($raw))) ? trim($raw) : null;

            if ($search) {
                $searchable = ['name','email','phone'];
                $available = array_filter($searchable, function ($col) {
                    return Schema::hasColumn('users', $col);
                });
                $available = array_values($available);

                $userModel = new User();
                $hasAnnexeRel = method_exists($userModel, 'annexe') || method_exists($userModel, 'annexes');

                if (count($available) || $hasAnnexeRel) {
                    $query->where(function ($q) use ($available, $search, $hasAnnexeRel) {
                        foreach ($available as $col) {
                            $q->orWhere($col, 'like', "%{$search}%");
                        }
                        // relation search only if relation exists on the model
                        if ($hasAnnexeRel) {
                            // try singular 'annexe' relation first, fallback to 'annexes'
                            if (method_exists(new User(), 'annexe')) {
                                $q->orWhereHas('annexe', function ($qa) use ($search) {
                                    $qa->where('name', 'like', "%{$search}%");
                                });
                            } elseif (method_exists(new User(), 'annexes')) {
                                $q->orWhereHas('annexes', function ($qa) use ($search) {
                                    $qa->where('name', 'like', "%{$search}%");
                                });
                            }
                        }
                    });
                }
            }

            // optional filters (annexe_id etc.)
            if ($annexeId = $request->get('annexe_id')) {
                $query->whereHas('annexes', function ($q) use ($annexeId, $institutionId, $platformAdmin) {
                    $q->where('annexes.id', $annexeId);

                    if (! $platformAdmin) {
                        $q->where('annexes.institution_id', $institutionId);
                    }
                });
            }

            // eager-load relations with nested relations for proper display
            $query = $query->with([
                'annexe',  // Primary annexe
                'user_annexes.role',  // All user_annexes with their role
                'user_annexes.annexe',  // All user_annexes with their annexe
            ]);

            if (Schema::hasColumn('users', 'name')) {
                $orderBy = 'name';
            } elseif (Schema::hasColumn('users', 'created_at')) {
                $orderBy = 'created_at';
            } else {
                $orderBy = 'id';
            }

            $users = $query->orderBy($orderBy)->paginate($perPage);

            return response()->json($users, 200);
        } catch (\Throwable $e) {
            \Log::error('UserController@index failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'request' => $request->all(),
            ]);
            return response()->json(['message' => 'Failed to fetch users.'], 500);
        }
    }
    
   public function show($id)
  {
    $institutionId = $this->getCurrentInstitutionId();
    $platformAdmin = method_exists(auth()->user(), 'isPlatformAdmin') && auth()->user()->isPlatformAdmin();
    if (!$institutionId && ! $platformAdmin) {
        abort(403, 'Institution introuvable pour cet utilisateur.');
    }

    $query = User::with([
        'annexe',
        'user_annexes.role',
        'user_annexes.annexe',
    ]);

    if (! $platformAdmin) {
        $query->whereHas('annexes', fn ($q) => $q->where('annexes.institution_id', $institutionId));
    }

    $user = $query->findOrFail($id);

    return response()->json($user);
  }

    public function store(StoreUserRequest $request)
    {
        $v = $request->validated();
        $institutionId = $this->getCurrentInstitutionId();
        $platformAdmin = method_exists(auth()->user(), 'isPlatformAdmin') && auth()->user()->isPlatformAdmin();

        if (!$institutionId && ! $platformAdmin) {
            return response()->json(['message' => 'Institution introuvable pour cet utilisateur.'], 403);
        }

        if (! $platformAdmin && !empty($v['annexe_id'])) {
            $annexeAllowed = \App\Models\Annexe::where('id', $v['annexe_id'])
                ->where('institution_id', $institutionId)
                ->exists();

            if (!$annexeAllowed) {
                return response()->json(['message' => 'Annexe hors de votre institution.'], 403);
            }
        }

        if ($request->hasFile('avatar')) {
            $v['avatar'] = $request->file('avatar')->store('avatars', 'public');
        }

        $plainPassword = $v['password'] ?? \Illuminate\Support\Str::password(12);

        if ($platformAdmin && empty($v['scope'])) {
            $v['scope'] = 'platform';
        }

        $user = User::create(array_merge($v, [
            'id' => (string) Str::uuid(),
            'password' => \Hash::make($plainPassword),
            'is_active' => $v['is_active'] ?? true,
        ]));

        if (!empty($v['role_id']) && !empty($v['annexe_id'])) {
            $role = Role::find($v['role_id']);
            if ($role) {
                $user->assignToAnnexe($v['annexe_id'], $role->id, true);
            }
        }

        // Recharger les relations pour le frontend
        $user->load([
            'annexe',
            'user_annexes.role',
            'user_annexes.annexe',
        ]);

        return response()->json(['message'=>'User created','user'=>$user], 201);
    }

    public function update(UpdateUserRequest $request, $id)
    {
        $institutionId = $this->getCurrentInstitutionId();
        $platformAdmin = method_exists(auth()->user(), 'isPlatformAdmin') && auth()->user()->isPlatformAdmin();
        if (!$institutionId && ! $platformAdmin) {
            abort(403, 'Institution introuvable pour cet utilisateur.');
        }

        $query = User::query();
        if (! $platformAdmin) {
            $query->whereHas('annexes', fn ($q) => $q->where('annexes.institution_id', $institutionId));
        }

        $user = $query->findOrFail($id);
        $v = $request->validated();

        if ($request->hasFile('avatar')) {
            if ($user->avatar) {
                Storage::disk('public')->delete($user->avatar);
            }
            $v['avatar'] = $request->file('avatar')->store('avatars', 'public');
        }

        if (!empty($v['password'])) {
            $v['password'] = \Hash::make($v['password']);
        } else {
            unset($v['password']);
        }

        $user->update($v);

        return response()->json(['message'=>'User updated','user'=>$user], 200);
    }

    public function destroy($id)
    {
        $institutionId = $this->getCurrentInstitutionId();
        $platformAdmin = method_exists(auth()->user(), 'isPlatformAdmin') && auth()->user()->isPlatformAdmin();
        if (!$institutionId && ! $platformAdmin) {
            abort(403, 'Institution introuvable pour cet utilisateur.');
        }

        $query = User::query();
        if (! $platformAdmin) {
            $query->whereHas('annexes', fn ($q) => $q->where('annexes.institution_id', $institutionId));
        }

        $user = $query->findOrFail($id);
        $user->delete();
        return response()->json(['message'=>'User deleted'], 200);
    }

    public function assignRole(Request $request, $id)
    {
        $data = $request->validate([
            'annexe_id' => 'required|uuid|exists:annexes,id',
            'role_id'   => 'required|uuid|exists:roles,id',
            'is_primary'=> 'sometimes|boolean'
        ]);

        $institutionId = $this->getCurrentInstitutionId();
        $platformAdmin = method_exists(auth()->user(), 'isPlatformAdmin') && auth()->user()->isPlatformAdmin();
        if (!$institutionId && ! $platformAdmin) {
            return response()->json(['message' => 'Institution introuvable pour cet utilisateur.'], 403);
        }

        $annexeAllowed = $platformAdmin
            ? \App\Models\Annexe::where('id', $data['annexe_id'])->exists()
            : \App\Models\Annexe::where('id', $data['annexe_id'])
                ->where('institution_id', $institutionId)
                ->exists();

        if (!$annexeAllowed) {
            return response()->json(['message' => 'Annexe hors de votre institution.'], 403);
        }

        $userQuery = User::query();
        if (! $platformAdmin) {
            $userQuery->whereHas('annexes', fn ($q) => $q->where('annexes.institution_id', $institutionId));
        }

        $user = $userQuery->findOrFail($id);

        // Autoriser via la policy UserPolicy::assignRole (vérifie que l'appelant a le droit)
        $this->authorize('assignRole', [$user, $data['annexe_id']]);

        $user->assignToAnnexe($data['annexe_id'], $data['role_id'], $data['is_primary'] ?? false);

        // Recharger les relations pour retourner l'utilisateur à jour
        $user->load([
            'annexe',
            'user_annexes.role',
            'user_annexes.annexe',
        ]);

        return response()->json(['message'=>'Role assigned', 'user'=>$user], 200);
    }

    public function removeRole(Request $request, $id)
    {
        $data = $request->validate([
            'annexe_id' => 'required|uuid|exists:annexes,id',
        ]);

        $institutionId = $this->getCurrentInstitutionId();
        $platformAdmin = method_exists(auth()->user(), 'isPlatformAdmin') && auth()->user()->isPlatformAdmin();
        if (!$institutionId && ! $platformAdmin) {
            return response()->json(['message' => 'Institution introuvable pour cet utilisateur.'], 403);
        }

        $annexeAllowed = $platformAdmin
            ? \App\Models\Annexe::where('id', $data['annexe_id'])->exists()
            : \App\Models\Annexe::where('id', $data['annexe_id'])
                ->where('institution_id', $institutionId)
                ->exists();

        if (!$annexeAllowed) {
            return response()->json(['message' => 'Annexe hors de votre institution.'], 403);
        }

        $userQuery = User::query();
        if (! $platformAdmin) {
            $userQuery->whereHas('annexes', fn ($q) => $q->where('annexes.institution_id', $institutionId));
        }

        $user = $userQuery->findOrFail($id);

        // Autoriser via la policy UserPolicy::removeRole
        $this->authorize('removeRole', [$user, $data['annexe_id']]);

        $user->removeFromAnnexe($data['annexe_id']);

        // Recharger les relations pour retourner l'utilisateur à jour
        $user->load([
            'annexe',
            'user_annexes.role',
            'user_annexes.annexe',
        ]);

        return response()->json(['message'=>'Role removed', 'user'=>$user], 200);
    }

    // Retourne l'utilisateur actuellement authentifié
    public function me(Request $request)
    {
        $user = $request->user();
        // charger relations courantes si besoin
        $user->loadMissing(['roles','annexes','annexe']);
        return response()->json(['user' => $user], 200);
    }

    // Met à jour l'utilisateur connecté (supporte PUT/PATCH/POST pour compatibilité frontend)
    public function updateMe(Request $request)
    {
        $user = $request->user();
        // validation minimale (adapter selon vos règles)
        $v = $request->validate([
            'name' => 'sometimes|string|max:255',
            'email' => 'sometimes|email|max:255|unique:users,email,'.$user->id,
            'phone' => 'sometimes|nullable|string|max:50',
            'bio' => 'sometimes|nullable|string|max:2000',
            'password' => 'sometimes|nullable|string|min:6|confirmed',
            'city' => 'sometimes|nullable|string|max:255',
            'state' => 'sometimes|nullable|string|max:255',
            'avatar' => 'nullable|image|mimes:jpeg,jpg,png,webp|max:2048',
        ]);

        // gérer l'avatar si fourni
        if ($request->hasFile('avatar')) {
            if ($user->avatar) {
                Storage::disk('public')->delete($user->avatar);
            }
            $user->avatar = $request->file('avatar')->store('avatars', 'public');
        }

        // gérer le mot de passe si fourni
        if (!empty($v['password'])) {
            $user->password = \Hash::make($v['password']);
            unset($v['password']);
            unset($v['password_confirmation']);
        }

        // mettre à jour les champs autorisés
        $updatable = array_intersect_key($v, array_flip(['name','email','phone','bio','city','state']));
        $user->fill($updatable);
        $user->save();

        // recharger relations si nécessaire et renvoyer
        $user->loadMissing(['roles','annexes','annexe']);
        return response()->json(['message' => 'Profile updated', 'user' => $user], 200);
    }
}