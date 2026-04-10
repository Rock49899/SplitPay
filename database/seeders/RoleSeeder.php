<?php

namespace Database\Seeders;

use App\Models\Role;
use Illuminate\Database\Seeder;

class RoleSeeder extends Seeder
{
    /**
     * ces rôles seront utilisés pour définir les permissions des utilisateurs
     */
    public function run(): void
    {
        $roles = [
            [
                'code' => 'super_admin_institution',
                'label' => 'Super Administrateur Institution',
                'description' => 'Accès total à toutes les annexes de l\'institution. Peut gérer les annexes, les utilisateurs et voir toutes les statistiques.',
                'scope' => 'institution',
            ],
            [
                'code' => 'super_admin_annexe',
                'label' => 'Super Administrateur Annexe',
                'description' => 'Accès total à son annexe. Peut tout gérer dans son annexe uniquement.',
                'scope' => 'annexe',
            ],
            [
                'code' => 'platform_admin',
                'label' => 'Administrateur Plateforme',
                'description' => 'Accès global à toute la plateforme. Peut gérer toutes les institutions, annexes, utilisateurs et statistiques.',
                'scope' => 'platform',
            ],
            [
                'code' => 'gestionnaire',
                'label' => 'Gestionnaire',
                'description' => 'Peut gérer les étudiants, créer des liens de paiement et envoyer des rappels dans son/ses annexes.',
                'scope' => 'annexe',
            ],
            [
                'code' => 'comptable',
                'label' => 'Comptable',
                'description' => 'Peut voir les paiements, les statistiques et exporter les données. Ne peut pas modifier les étudiants.',
                'scope' => 'annexe',
            ],
        ];

        $count = 0;
        foreach ($roles as $roleData) {
            $role = Role::updateOrCreate(
                ['code' => $roleData['code']],
                $roleData
            );
            $count++;
            dump("Role cree/mis a jour: {$role->code}");
        }

        dump("Total roles traites: {$count}");
        $this->command->info('5 rôles créés/mis à jour avec succès !');
    }
}
