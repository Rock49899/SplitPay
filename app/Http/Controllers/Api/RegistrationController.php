<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;

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

        if (! empty($data['institution_email']) && Institution::query()->where('email', $data['institution_email'])->exists()) {
            return response()->json([
                'message' => 'Cet e-mail institution existe déjà.'
            ], 422);
        }

        if (Institution::query()->whereRaw('LOWER(name) = ?', [mb_strtolower($data['institution_name'])])->exists()) {
            return response()->json([
                'message' => 'Ce nom d\'institution existe déjà.'
            ], 422);
        }

        $result = DB::transaction(function () use ($data, $request) {
            // Gérer l'upload du logo si fourni
            $logoPath = null;
            if ($request->hasFile('logo')) {
                $logoPath = $request->file('logo')->store('logos', 'public');
            }
            
            // Create institution
            $institution = Institution::create([
                'name' => $data['institution_name'],
                'email' => $data['institution_email'] ?? null,
                'phone' => $data['institution_phone'] ?? null,
                'logo' => $logoPath,
                'is_active' => true,
            ]);

            // Décoder annexe_details si fourni
            $annexeDetails = null;
            if (isset($data['annexe_details'])) {
                $annexeDetails = is_string($data['annexe_details']) 
                    ? json_decode($data['annexe_details'], true) 
                    : $data['annexe_details'];
            }
            
            // annexe principale
            $annexe = Annexe::create([
                'institution_id' => $institution->id,
                'name' => $data['annexe_name'] ?? ($institution->name . ' - Principal'),
                'annexe_details' => $annexeDetails,
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
            'user' => [
                'id' => $result['user']->id,
                'name' => $result['user']->name,
                'email' => $result['user']->email,
            ],
        ], 201);
    }
}
