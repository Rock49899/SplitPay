<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use App\Models\User;
use App\Models\Role;
use App\Http\Requests\StoreUserRequest;
use App\Http\Requests\UpdateUserRequest;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\QueryException;

class UserController extends Controller
{
    public function __construct()
    {
        // protéger ces routes : requiert authentification API (Sanctum)
        $this->middleware('auth:sanctum');
            }

    public function index(Request $request)
    {
        $perPage = (int) $request->get('per_page', 15);

        $query = User::query();

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
                $query->where('annexe_id', $annexeId);
            }

            // eager-load only relations that exist on the model
            $with = [];
            $userModel = new User();
            if (method_exists($userModel, 'annexes')) $with[] = 'annexes';
            if (method_exists($userModel, 'user_annexes')) $with[] = 'user_annexes';
            if (method_exists($userModel, 'roles')) $with[] = 'roles';
            if (method_exists($userModel, 'annexe')) $with[] = 'annexe';
            // apply if any
            if (count($with)) {
                $query = $query->with($with);
            }

            // safe order by: prefer name if column exists
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
    $user = User::with([
        'roles',
        'annexes',
        'annexe'
    ])->findOrFail($id);

    return response()->json($user);
  }



    // public function show($id)
    // {
    //     $user = User::with(['roles','annexes'])->findOrFail($id);
    //     return response()->json($user, 200);
    // }

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