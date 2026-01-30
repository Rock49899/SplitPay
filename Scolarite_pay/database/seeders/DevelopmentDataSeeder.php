<?php

namespace Database\Seeders;

use App\Models\Institution;
use App\Models\Annexe;
use App\Models\User;
use App\Models\Role;
use App\Models\Student;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DevelopmentDataSeeder extends Seeder
{
    //données tests
    public function run(): void
    {
        $this->command->info('Création des données de test...');
        $this->command->newLine();

        $this->command->info('Création de l\'institution...');
        
        $institution = Institution::create([
            'name' => 'Institut Supérieur de Technologie',
            'email' => 'contact@ist-edu.com',
            'phone' => '+229 97 00 00 00',
            'address' => 'Avenue de la République',
            'city' => 'Cotonou',
            'is_active' => true,
        ]);
        
        $this->command->info('Institution créée : ' . $institution->name);

        $this->command->info('Création des annexes');
        
        $annexeNord = Annexe::create([
            'institution_id' => $institution->id,
            'name' => 'Campus Nord',
            'address' => 'Quartier Akpakpa',
            'city' => 'Cotonou',
            'is_active' => true,
        ]);
        
        $annexeSud = Annexe::create([
            'institution_id' => $institution->id,
            'name' => 'Campus Sud',
            'address' => 'Quartier Fidjrossè',
            'city' => 'Cotonou',
            'is_active' => true,
        ]);
        
        $this->command->info('2 annexes créées : Campus Nord, Campus Sud');

        $roleSuperAdminInstitution = Role::where('code', 'super_admin_institution')->first();
        $roleSuperAdminAnnexe = Role::where('code', 'super_admin_annexe')->first();
        $roleGestionnaire = Role::where('code', 'gestionnaire')->first();

       
        $this->command->info('Création du Super Admin Institution');
        
        $superAdmin = User::create([
            'annexe_id' => $annexeNord->id, // Annexe principale
            'name' => 'Admin Principal',
            'email' => 'admin@ist-edu.com',
            'password' => Hash::make('password'), 
            'phone' => '+229 97 11 11 11',
            'is_active' => true,
            'scope' => 'institution',
        ]);
        
        // Assigner le rôle via user_annexes
        $superAdmin->annexes()->attach($annexeNord->id, [
            'role_id' => $roleSuperAdminInstitution->id,
            'is_principal' => true,
            'assigned_by' => null,
            'assigned_at' => now(),
        ]);
        
        $this->command->info(' Super Admin : admin@ist-edu.com | password');

        $this->command->info('Création du gestionnaire Campus Nord...');
        
        $gestionnaireNord = User::create([
            'annexe_id' => $annexeNord->id,
            'name' => 'Marie Dupont',
            'email' => 'marie@ist-edu.com',
            'password' => Hash::make('password'),
            'phone' => '+229 97 22 22 22',
            'is_active' => true,
            'scope' => 'annexe',
        ]);
        
        $gestionnaireNord->annexes()->attach($annexeNord->id, [
            'role_id' => $roleGestionnaire->id,
            'is_principal' => true,
            'assigned_by' => $superAdmin->id,
            'assigned_at' => now(),
        ]);
        
      
        $this->command->info(' Gestionnaire Nord : marie@ist-edu.com | password');

        $this->command->info('Création du gestionnaire Campus Sud...');
        
        $gestionnaireSud = User::create([
            'annexe_id' => $annexeSud->id,
            'name' => 'Jean Martin',
            'email' => 'jean@ist-edu.com',
            'password' => Hash::make('password'),
            'phone' => '+229 97 33 33 33',
            'is_active' => true,
            'scope' => 'annexe',
        ]);
        
        $gestionnaireSud->annexes()->attach($annexeSud->id, [
            'role_id' => $roleGestionnaire->id,
            'is_principal' => true,
            'assigned_by' => $superAdmin->id,
            'assigned_at' => now(),
        ]);
        
        $this->command->info(' Gestionnaire Sud : jean@ist-edu.com | password');

        $this->command->info('Création des étudiants Campus Nord...');
        
        $etudiantsNord = [
            ['matricule' => 'ETU2024001', 'first_name' => 'Amina', 'last_name' => 'Kouassi', 'class' => 'Licence 1 Info'],
            ['matricule' => 'ETU2024002', 'first_name' => 'Koffi', 'last_name' => 'Mensah', 'class' => 'Licence 1 Info'],
            ['matricule' => 'ETU2024003', 'first_name' => 'Fatoumata', 'last_name' => 'Diallo', 'class' => 'Licence 2 Info'],
            ['matricule' => 'ETU2024004', 'first_name' => 'Ibrahim', 'last_name' => 'Traoré', 'class' => 'Licence 2 Info'],
            ['matricule' => 'ETU2024005', 'first_name' => 'Aïcha', 'last_name' => 'Camara', 'class' => 'Master 1 Info'],
        ];
        
        foreach ($etudiantsNord as $etudiantData) {
            Student::create(array_merge($etudiantData, [
                'annexe_id' => $annexeNord->id,
                'email' => strtolower($etudiantData['first_name']) . '@etudiant.com',
                'phone' => '+229 97 ' . rand(40, 49) . ' ' . rand(10, 99) . ' ' . rand(10, 99),
                'school_year' => '2024-2025',
                'tuition_amount' => 500000,
                'amount_paid' => rand(0, 500000),
                'status' => 'active',
            ]));
        }
        
        $this->command->info(' 5 étudiants créés pour Campus Nord');

        $this->command->info('Création des étudiants Campus Sud');
        
        $etudiantsSud = [
            ['matricule' => 'ETU2024006', 'first_name' => 'Sébastien', 'last_name' => 'Kouadio', 'class' => 'Licence 1 Gestion'],
            ['matricule' => 'ETU2024007', 'first_name' => 'Aminata', 'last_name' => 'Sow', 'class' => 'Licence 1 Gestion'],
            ['matricule' => 'ETU2024008', 'first_name' => 'Moussa', 'last_name' => 'Keita', 'class' => 'Licence 2 Gestion'],
            ['matricule' => 'ETU2024009', 'first_name' => 'Mariama', 'last_name' => 'Barry', 'class' => 'Licence 2 Gestion'],
            ['matricule' => 'ETU2024010', 'first_name' => 'Youssouf', 'last_name' => 'Touré', 'class' => 'Master 1 Gestion'],
        ];
        
        foreach ($etudiantsSud as $etudiantData) {
            Student::create(array_merge($etudiantData, [
                'annexe_id' => $annexeSud->id,
                'email' => strtolower($etudiantData['first_name']) . '@etudiant.com',
                'phone' => '+229 97 ' . rand(50, 59) . ' ' . rand(10, 99) . ' ' . rand(10, 99),
                'school_year' => '2024-2025',
                'tuition_amount' => 450000,
                'amount_paid' => rand(0, 450000),
                'status' => 'active',
            ]));
        }
        
        $this->command->info(' 5 étudiants créés pour Campus Sud');

        $this->command->newLine();
        $this->command->info('Données de test créées avec succès !');
        $this->command->newLine();
        $this->command->info('RÉSUMÉ :');
        $this->command->info('   - 1 institution');
        $this->command->info('   - 2 annexes (Campus Nord, Campus Sud)');
        $this->command->info('   - 3 utilisateurs (1 Super Admin + 2 Gestionnaires)');
        $this->command->info('   - 10 étudiants (5 par campus)');
        $this->command->newLine();
        $this->command->info('CONNEXION :');
        $this->command->info('   Email    : admin@ist-edu.com');
        $this->command->info('   Password : password');
    }
}
