<?php

namespace Database\Seeders;

use App\Models\Role;
use App\Models\Permission;
use Illuminate\Database\Seeder;

class RolePermissionSeeder extends Seeder
{
 
    public function run(): void
    {
        // Récupérer tous les rôles créés précédemment
        $superAdminInstitution = Role::where('code', 'super_admin_institution')->first();
        $superAdminAnnexe = Role::where('code', 'super_admin_annexe')->first();
        $gestionnaire = Role::where('code', 'gestionnaire')->first();
        $comptable = Role::where('code', 'comptable')->first();

        // sup admin institution, il a toutes les permissions
        $allPermissions = Permission::all()->pluck('id')->toArray();
        $superAdminInstitution->permissions()->sync($allPermissions);
        $this->command->info('Super Admin Institution : ' . count($allPermissions) . ' permissions');

        // sup admin annexe peut tout, sauf la gestion des annexes..
        $superAdminAnnexePermissions = Permission::whereNotIn('code', [
            'annexe.create',   
            'annexe.delete',   
            'dashboard.multi_annexe', 
        ])->pluck('id')->toArray();
        
        $superAdminAnnexe->permissions()->sync($superAdminAnnexePermissions);
        $this->command->info('Super Admin Annexe : ' . count($superAdminAnnexePermissions) . ' permissions');

        // gestionnaire: uniquement gérer les étudiants et liens de paiementsn et rappels
        $gestionnairePermissions = Permission::whereIn('code', [
            // tout sauf supprimer etudiant
            'student.view',
            'student.create',
            'student.edit',
            'student.import',
            
            'payment.view',
            'payment.statistics',
            
            'link.view',
            'link.create',
            'link.send',
            'link.cancel',
            
            'reminder.view',
            'reminder.create',
            'reminder.edit',
            'reminder.delete',
            
            'annexe.view',  // Besoin de voir les annexes pour filtrer les données
            
            'dashboard.view',
            'dashboard.statistics',
        ])->pluck('id')->toArray();
        
        $gestionnaire->permissions()->sync($gestionnairePermissions);
        $this->command->info('Gestionnaire : ' . count($gestionnairePermissions) . ' permissions');

        // comptable: consulte les paiement, voit étudiants et exporte données
        $comptablePermissions = Permission::whereIn('code', [
            'student.view',
            
            'payment.view',
            'payment.export',
            'payment.statistics',
            
            'link.view',
            
            'annexe.view',  // Besoin de voir les annexes pour filtrer les données
            
            'dashboard.view',
            'dashboard.statistics',
        ])->pluck('id')->toArray();
        
        $comptable->permissions()->sync($comptablePermissions);
        $this->command->info('Comptable : ' . count($comptablePermissions) . ' permissions');

        // Message final
        $this->command->info('');
        $this->command->info('Toutes les permissions ont été attribuées aux rôles !');
    }
}
