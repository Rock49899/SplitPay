<?php

namespace Database\Seeders;

use App\Models\Permission;
use Illuminate\Database\Seeder;

class PermissionSeeder extends Seeder
{
    // permissions organisées par module (students, payments, users, etc.)
    
    public function run(): void
    {
        $permissions = [
            
            [
                'code' => 'student.view',
                'label' => 'Voir les étudiants',
                'description' => 'Peut consulter la liste des étudiants et leurs détails',
                'module' => 'students',
            ],
            [
                'code' => 'student.create',
                'label' => 'Créer un étudiant',
                'description' => 'Peut ajouter un nouvel étudiant',
                'module' => 'students',
            ],
            [
                'code' => 'student.edit',
                'label' => 'Modifier un étudiant',
                'description' => 'Peut modifier les informations d\'un étudiant',
                'module' => 'students',
            ],
            [
                'code' => 'student.delete',
                'label' => 'Supprimer un étudiant',
                'description' => 'Peut supprimer un étudiant',
                'module' => 'students',
            ],
            [
                'code' => 'student.import',
                'label' => 'Importer des étudiants',
                'description' => 'Peut importer une liste d\'étudiants via Excel',
                'module' => 'students',
            ],

            // Gestion des paiements
            [
                'code' => 'payment.view',
                'label' => 'Voir les paiements',
                'description' => 'Peut consulter l\'historique des paiements',
                'module' => 'payments',
            ],
            [
                'code' => 'payment.export',
                'label' => 'Exporter les paiements',
                'description' => 'Peut exporter les paiements en Excel',
                'module' => 'payments',
            ],
            [
                'code' => 'payment.statistics',
                'label' => 'Voir les statistiques de paiement',
                'description' => 'Peut voir les graphiques et statistiques de collecte',
                'module' => 'payments',
            ],

            // Gestion des liens de paiement
            [
                'code' => 'link.view',
                'label' => 'Voir les liens de paiement',
                'description' => 'Peut consulter la liste des liens générés',
                'module' => 'payment_links',
            ],
            [
                'code' => 'link.create',
                'label' => 'Créer un lien de paiement',
                'description' => 'Peut générer un nouveau lien de paiement',
                'module' => 'payment_links',
            ],
            [
                'code' => 'link.send',
                'label' => 'Envoyer un lien par email',
                'description' => 'Peut envoyer le lien de paiement à l\'étudiant',
                'module' => 'payment_links',
            ],
            [
                'code' => 'link.cancel',
                'label' => 'Annuler un lien',
                'description' => 'Peut annuler/désactiver un lien de paiement',
                'module' => 'payment_links',
            ],

            // rappels automatiques
            [
                'code' => 'reminder.view',
                'label' => 'Voir les rappels',
                'description' => 'Peut consulter les rappels configurés',
                'module' => 'reminders',
            ],
            [
                'code' => 'reminder.create',
                'label' => 'Créer un rappel',
                'description' => 'Peut configurer un nouveau rappel automatique',
                'module' => 'reminders',
            ],
            [
                'code' => 'reminder.edit',
                'label' => 'Modifier un rappel',
                'description' => 'Peut modifier le message ou le délai d\'un rappel',
                'module' => 'reminders',
            ],
            [
                'code' => 'reminder.delete',
                'label' => 'Supprimer un rappel',
                'description' => 'Peut supprimer un rappel automatique',
                'module' => 'reminders',
            ],

            // destion utilisateurs
            [
                'code' => 'user.view',
                'label' => 'Voir les utilisateurs',
                'description' => 'Peut consulter la liste des utilisateurs',
                'module' => 'users',
            ],
            [
                'code' => 'user.create',
                'label' => 'Créer un utilisateur',
                'description' => 'Peut ajouter un nouvel utilisateur (admin, gestionnaire, etc.)',
                'module' => 'users',
            ],
            [
                'code' => 'user.edit',
                'label' => 'Modifier un utilisateur',
                'description' => 'Peut modifier les informations d\'un utilisateur',
                'module' => 'users',
            ],
            [
                'code' => 'user.delete',
                'label' => 'Supprimer un utilisateur',
                'description' => 'Peut supprimer un compte utilisateur',
                'module' => 'users',
            ],
            [
                'code' => 'user.manage_roles',
                'label' => 'Gérer les rôles',
                'description' => 'Peut assigner ou retirer des rôles aux utilisateurs',
                'module' => 'users',
            ],
            [
                'code' => 'user.assign_annexe',
                'label' => 'Assigner à des annexes',
                'description' => 'Peut assigner un utilisateur à plusieurs annexes',
                'module' => 'users',
            ],

            // gestion des annexes
            [
                'code' => 'annexe.view',
                'label' => 'Voir les annexes',
                'description' => 'Peut consulter la liste des annexes',
                'module' => 'annexes',
            ],
            [
                'code' => 'annexe.create',
                'label' => 'Créer une annexe',
                'description' => 'Peut créer une nouvelle annexe/campus',
                'module' => 'annexes',
            ],
            [
                'code' => 'annexe.edit',
                'label' => 'Modifier une annexe',
                'description' => 'Peut modifier les informations d\'une annexe',
                'module' => 'annexes',
            ],
            [
                'code' => 'annexe.delete',
                'label' => 'Supprimer une annexe',
                'description' => 'Peut supprimer une annexe',
                'module' => 'annexes',
            ],

            // tableaux de bord
            [
                'code' => 'dashboard.view',
                'label' => 'Voir le dashboard',
                'description' => 'Peut accéder au tableau de bord',
                'module' => 'dashboard',
            ],
            [
                'code' => 'dashboard.statistics',
                'label' => 'Voir les statistiques',
                'description' => 'Peut voir les graphiques et indicateurs',
                'module' => 'dashboard',
            ],
            [
                'code' => 'dashboard.multi_annexe',
                'label' => 'Dashboard multi-annexes',
                'description' => 'Peut voir le dashboard consolidé de toutes les annexes',
                'module' => 'dashboard',
            ],
        ];

        // Créer chaque permission dans la base de données
        foreach ($permissions as $permissionData) {
            Permission::create($permissionData);
        }

        // Message de confirmation
        $this->command->info(count($permissions) . ' permissions créées avec succès !');
    }
}
