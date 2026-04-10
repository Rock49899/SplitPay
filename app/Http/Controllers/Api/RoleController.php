<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Role;

class RoleController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth:sanctum');
    }

    // Retourne la liste des rôles (id, code, label, scope)

    public function index(Request $request)
    {
        $user = auth()->user();
        $isPlatformAdmin = $user && method_exists($user, 'isPlatformAdmin') && $user->isPlatformAdmin();

        $query = Role::query()->orderBy('label');

        // Le rôle platform_admin est réservé à l'admin plateforme
        // et ne doit jamais être proposé aux admins institution/annexe.
        if (! $isPlatformAdmin) {
            $query->where('code', '!=', 'platform_admin');
        }

        $roles = $query->get(['id','code','label','scope','description']);
        return response()->json($roles, 200);
    }
}
