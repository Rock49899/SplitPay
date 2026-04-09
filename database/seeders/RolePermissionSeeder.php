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
        $platformAdmin = Role::where('code', 'platform_admin')->first();
        $superAdminAnnexe = Role::where('code', 'super_admin_annexe')->first();
        $gestionnaire = Role::where('code', 'gestionnaire')->first();
        $comptable = Role::where('code', 'comptable')->first();

        // sup admin institution, il a toutes les permissions
        $allPermissions = Permission::all()->pluck('id')->toArray();
        if ($superAdminInstitution) {
            $superAdminInstitution->permissions()->sync($allPermissions);
            $this->command->info('Super Admin Institution : ' . count($allPermissions) . ' permissions');
        }

        if ($platformAdmin) {
            $platformAdmin->permissions()->sync($allPermissions);
            $this->command->info('Administrateur Plateforme : ' . count($allPermissions) . ' permissions');
        }

        // sup admin annexe peut tout, sauf la gestion des annexes..
        $superAdminAnnexePermissions = Permission::whereNotIn('code', [
            'annexe.create',   
            'annexe.delete',   
            'dashboard.multi_annexe', 
        ])->pluck('id')->toArray();
        
        if ($superAdminAnnexe) {
            $superAdminAnnexe->permissions()->sync($superAdminAnnexePermissions);
            $this->command->info('Super Admin Annexe : ' . count($superAdminAnnexePermissions) . ' permissions');
        }

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
            
            'notification.view',
            'notification.manage',
            
            'reminder.view',
            'reminder.create',
            'reminder.edit',
            'reminder.delete',
            
            'annexe.view',  // Besoin de voir les annexes pour filtrer les données
            
            'dashboard.view',
            'dashboard.statistics',
        ])->pluck('id')->toArray();
        
        if ($gestionnaire) {
            $gestionnaire->permissions()->sync($gestionnairePermissions);
            $this->command->info('Gestionnaire : ' . count($gestionnairePermissions) . ' permissions');
        }

        // comptable: consulte les paiement, voit étudiants et exporte données
        $comptablePermissions = Permission::whereIn('code', [
            'student.view',
            
            'payment.view',
            'payment.export',
            'payment.statistics',
            
            'link.view',
            
            'notification.view',
            'notification.manage',
            
            'annexe.view',  // Besoin de voir les annexes pour filtrer les données
            
            'dashboard.view',
            'dashboard.statistics',
        ])->pluck('id')->toArray();
        
        if ($comptable) {
            $comptable->permissions()->sync($comptablePermissions);
            $this->command->info('Comptable : ' . count($comptablePermissions) . ' permissions');
        }

        // Message final
        $this->command->info('');
        $this->command->info('Toutes les permissions ont été attribuées aux rôles !');
    }
}
