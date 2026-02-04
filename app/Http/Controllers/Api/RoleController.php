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
        $roles = Role::orderBy('label')->get(['id','code','label','scope','description']);
        return response()->json($roles, 200);
    }
}
