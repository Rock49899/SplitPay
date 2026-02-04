<?php

namespace Tests\Feature;

use App\Models\Role;
use App\Models\Permission;
use App\Models\User;
use App\Models\Institution;
use App\Models\Annexe;
use App\Models\Student;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DatabaseCoherenceTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        
        // Lancer les seeders avant chaque test
        $this->seed();
    }

    
    //  Test 1: Verifier le nombre de roles crees
     
    public function test_roles_count()
    {
        $count = Role::count();
        
        dump("Roles crees: {$count}");
        
        $this->assertEquals(4, $count, "Il devrait y avoir 4 roles");
    }

    
    // Test 2: Verifier le nombre de permissions creees
    
    public function test_permissions_count()
    {
        $count = Permission::count();
        
        dump("Permissions creees: {$count}");
        
        $this->assertEquals(29, $count, "Il devrait y avoir 29 permissions");
    }

    //  Test 3: Verifier que chaque role a ses permissions
    
    public function test_roles_have_permissions()
    {
        $superAdminInstitution = Role::where('code', 'super_admin_institution')->first();
        $superAdminAnnexe = Role::where('code', 'super_admin_annexe')->first();
        $gestionnaire = Role::where('code', 'gestionnaire')->first();
        $comptable = Role::where('code', 'comptable')->first();

        // Super Admin Institution doit avoir toutes les permissions
        $countSuperAdmin = $superAdminInstitution->permissions()->count();
        dump("Super Admin Institution a {$countSuperAdmin} permissions");
        $this->assertEquals(29, $countSuperAdmin);

        // Super Admin Annexe doit avoir 26 permissions (29 - 3 exclues)
        $countSuperAnnexe = $superAdminAnnexe->permissions()->count();
        dump("Super Admin Annexe a {$countSuperAnnexe} permissions");
        $this->assertEquals(26, $countSuperAnnexe);

        // Gestionnaire doit avoir 16 permissions
        $countGestionnaire = $gestionnaire->permissions()->count();
        dump("Gestionnaire a {$countGestionnaire} permissions");
        $this->assertEquals(16, $countGestionnaire);

        // Comptable doit avoir 7 permissions
        $countComptable = $comptable->permissions()->count();
        dump("Comptable a {$countComptable} permissions");
        $this->assertEquals(7, $countComptable);
    }

    
    //  Test 4: Verifier le nombre d'utilisateurs
     
    public function test_users_count()
    {
        $count = User::count();
        
        dump("Utilisateurs crees: {$count}");
        
        $this->assertEquals(3, $count, "Il devrait y avoir 3 utilisateurs");
    }

   
    //  Test 5: Verifier que les utilisateurs ont des annexes
     
    public function test_users_have_annexes()
    {
        $superAdmin = User::where('email', 'admin@ist-edu.com')->first();
        $marie = User::where('email', 'marie@ist-edu.com')->first();
        $jean = User::where('email', 'jean@ist-edu.com')->first();

        // Verifier que chaque user a au moins 1 annexe
        $this->assertGreaterThanOrEqual(1, $superAdmin->annexes()->count());
        $this->assertGreaterThanOrEqual(1, $marie->annexes()->count());
        $this->assertGreaterThanOrEqual(1, $jean->annexes()->count());

        dump("Super Admin a acces a " . $superAdmin->annexes()->count() . " annexe(s)");
        dump("Marie a acces a " . $marie->annexes()->count() . " annexe(s)");
        dump("Jean a acces a " . $jean->annexes()->count() . " annexe(s)");
    }

    //  Test 6: Verifier le nombre d'institutions et d'annexes
     
    public function test_institutions_and_annexes_count()
    {
        $institutionCount = Institution::count();
        $annexeCount = Annexe::count();

        dump("Institutions: {$institutionCount}");
        dump("Annexes: {$annexeCount}");

        $this->assertEquals(1, $institutionCount, "Il devrait y avoir 1 institution");
        $this->assertEquals(2, $annexeCount, "Il devrait y avoir 2 annexes");
    }

    //  7: Verifier le nombre d'etudiants
     
    public function test_students_count()
    {
        $count = Student::count();
        
        dump("Etudiants crees: {$count}");
        
        $this->assertEquals(10, $count, "Il devrait y avoir 10 etudiants");
    }

    //  test 8: Verifier que chaque annexe a ses etudiants
     
    public function test_each_annexe_has_students()
    {
        $campusNord = Annexe::where('name', 'Campus Nord')->first();
        $campusSud = Annexe::where('name', 'Campus Sud')->first();

        $nordCount = Student::where('annexe_id', $campusNord->id)->count();
        $sudCount = Student::where('annexe_id', $campusSud->id)->count();

        dump("Campus Nord: {$nordCount} etudiants");
        dump("Campus Sud: {$sudCount} etudiants");

        $this->assertEquals(5, $nordCount, "Campus Nord devrait avoir 5 etudiants");
        $this->assertEquals(5, $sudCount, "Campus Sud devrait avoir 5 etudiants");
    }

    //  Test 9: Verifier que les relations fonctionnent
     
    public function test_relations_work()
    {
        // Tester relation User -> Annexe principale
        $user = User::first();
        $this->assertNotNull($user->annexe);
        dump("User a une annexe principale: " . $user->annexe->name);

        // Tester relation Student -> Annexe
        $student = Student::first();
        $this->assertNotNull($student->annexe);
        dump("Etudiant appartient a: " . $student->annexe->name);

        // Tester relation Annexe -> Institution
        $annexe = Annexe::first();
        $this->assertNotNull($annexe->institution);
        dump("Annexe appartient a: " . $annexe->institution->name);
    }

    //  Test 10: Verifier que Super Admin Institution n'a pas le scope annexe actif
     
    public function test_super_admin_can_see_all_students()
    {
        $superAdmin = User::where('email', 'admin@ist-edu.com')->first();
        
        // Se connecter en tant que Super Admin (simuler)
        $this->actingAs($superAdmin);
        
        // Le Super Admin devrait voir tous les etudiants (10)
        // global Scope sera teste plus tard avec l'authentification
        $count = Student::count();
        
        dump("Super Admin voit {$count} etudiants");
        
        $this->assertEquals(10, $count);
    }
}
