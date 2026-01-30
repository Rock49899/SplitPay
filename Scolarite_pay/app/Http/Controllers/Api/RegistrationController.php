<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreInstitutionRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;
use App\Models\Institution;
use App\Models\Annexe;
use App\Models\User;
use App\Models\Role;

class RegistrationController extends Controller
{
    // inscription institution avec annexe principale et super admin instition

    public function register(StoreInstitutionRequest $request)
    {
        $data = $request->validated();

        $result = DB::transaction(function () use ($data) {
            // Create institution
            $institution = Institution::create([
                'name' => $data['institution_name'],
                'email' => $data['institution_email'] ?? null,
                'phone' => $data['institution_phone'] ?? null,
                'is_active' => true,
            ]);

            // annexe principale
            $annexe = Annexe::create([
                'institution_id' => $institution->id,
                'name' => $data['annexe_name'] ?? ($institution->name . ' - Principal'),
                'is_active' => true,
            ]);

            // super admin
            $user = User::create([
                'name' => $data['owner_name'],
                'email' => $data['owner_email'],
                'password' => Hash::make($data['owner_password']),
                'phone' => null,
                'is_active' => true,
                'scope' => 'institution',
                'annexe_id' => $annexe->id,
            ]);

            // Assign super admin institution role if exists
            $role = Role::where('code', 'super_admin_institution')->first();
            if ($role) {
                $user->assignToAnnexe($annexe->id, $role->id, true);
            }

            return compact('institution', 'annexe', 'user');
        });

        return response()->json([
            'message' => 'Institution creee avec succes',
            'institution' => [
                'id' => $result['institution']->id,
                'name' => $result['institution']->name,
            ],
            'annexe' => [
                'id' => $result['annexe']->id,
                'name' => $result['annexe']->name,
            ],
            'owner' => [
                'id' => $result['user']->id,
                'name' => $result['user']->name,
                'email' => $result['user']->email,
            ],
        ], 201);
    }
}
