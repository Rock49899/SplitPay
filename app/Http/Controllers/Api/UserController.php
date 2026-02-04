<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use App\Models\User;
use App\Models\Role;
use App\Http\Requests\StoreUserRequest;
use App\Http\Requests\UpdateUserRequest;

class UserController extends Controller
{
    public function __construct()
    {
        // protéger ces routes : requiert authentification API (Sanctum)
        $this->middleware('auth:sanctum');
        // NOTE: appliquer une policy/middleware pour restreindre ces actions aux rôles appropriés
        // Ex: only users with 'super_admin_institution' or 'gestionnaire' can accéder ici.
    }

    public function index(Request $request)
    {
        $perPage = (int) $request->get('per_page', 15);
        $query = User::query();

        if ($q = $request->get('q')) {
            $query->where(function($qr) use ($q) {
                $qr->where('name','like',"%{$q}%")
                   ->orWhere('email','like',"%{$q}%")
                   ->orWhere('phone','like',"%{$q}%");
            });
        }

        return response()->json($query->orderBy('name')->paginate($perPage));
    }

    public function show($id)
    {
        $user = User::with(['roles','annexes'])->findOrFail($id);
        return response()->json($user, 200);
    }

    public function store(StoreUserRequest $request)
    {
        $v = $request->validated();

        $user = User::create(array_merge($v, [
            'id' => (string) Str::uuid(),
            'password' => isset($v['password']) ? \Hash::make($v['password']) : null,
            'is_active' => $v['is_active'] ?? true,
        ]));

        if (!empty($v['role_id']) && !empty($v['annexe_id'])) {
            $role = Role::find($v['role_id']);
            if ($role) {
                $user->assignToAnnexe($v['annexe_id'], $role->id, true);
            }
        }

        return response()->json(['message'=>'User created','user'=>$user], 201);
    }

    public function update(UpdateUserRequest $request, $id)
    {
        $user = User::findOrFail($id);
        $v = $request->validated();

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
        $user = User::findOrFail($id);
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

        $user = User::findOrFail($id);

        // Authorize using UserPolicy::assignRole
        $this->authorize('assignRole', [$user, $data['annexe_id']]);

        $user->assignToAnnexe($data['annexe_id'], $data['role_id'], $data['is_primary'] ?? false);

        return response()->json(['message'=>'Role assigned'], 200);
    }

    public function removeRole(Request $request, $id)
    {
        $data = $request->validate([
            'annexe_id' => 'required|uuid|exists:annexes,id',
            'role_id'   => 'required|uuid|exists:roles,id',
        ]);

        $user = User::findOrFail($id);

        // Authorize using UserPolicy::removeRole
        $this->authorize('removeRole', [$user, $data['annexe_id']]);

        $user->removeFromAnnexe($data['annexe_id'], $data['role_id']);

        return response()->json(['message'=>'Role removed'], 200);
    }
}