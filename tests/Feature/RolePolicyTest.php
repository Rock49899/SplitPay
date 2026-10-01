<?php

namespace Tests\Feature;

use App\Models\Annexe;
use App\Models\Institution;
use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RolePolicyTest extends TestCase
{
    use RefreshDatabase;

    public function test_assign_and_remove_role_authorization(): void
    {
        $institution = Institution::create(['name' => 'Ecole Roles', 'email' => 'roles@example.com', 'is_active' => true]);
        $annexe = Annexe::create(['institution_id' => $institution->id, 'name' => 'Campus Roles', 'is_active' => true]);

        $manageRoles = Permission::create(['code' => 'user.manage_roles', 'label' => 'Gérer les rôles', 'module' => 'users']);

        $superRole = Role::create(['code' => 'super_admin_institution', 'label' => 'Super admin institution', 'scope' => 'institution']);
        $superRole->permissions()->attach($manageRoles->id);

        $managerRole = Role::create(['code' => 'gestionnaire', 'label' => 'Gestionnaire', 'scope' => 'annexe']);

        // Super admin de l'institution, rattaché à son annexe principale
        $super = User::create([
            'name' => 'Super',
            'email' => 'super@example.com',
            'password' => 'secret-password',
            'scope' => 'institution',
            'is_active' => true,
        ]);
        $super->assignToAnnexe($annexe->id, $superRole->id, true);

        // Nouvel utilisateur, pas encore affecté
        $target = User::create([
            'name' => 'Target',
            'email' => 'target@example.com',
            'password' => 'secret-password',
            'scope' => 'annexe',
            'is_active' => true,
        ]);

        $this->actingAs($super, 'sanctum');

        $this->postJson("/api/admin/users/{$target->id}/assign-role", [
            'annexe_id' => $annexe->id,
            'role_id' => $managerRole->id,
            'is_primary' => true,
        ])->assertOk();

        $this->assertTrue($target->fresh()->annexes()->where('annexes.id', $annexe->id)->exists());

        $this->postJson("/api/admin/users/{$target->id}/remove-role", [
            'annexe_id' => $annexe->id,
        ])->assertOk();

        $this->assertFalse($target->fresh()->annexes()->where('annexes.id', $annexe->id)->exists());

        // Un gestionnaire (sans la permission user.manage_roles) ne peut pas attribuer de rôle
        $manager = User::create([
            'name' => 'Gestionnaire',
            'email' => 'gestionnaire@example.com',
            'password' => 'secret-password',
            'scope' => 'annexe',
            'is_active' => true,
        ]);
        $manager->assignToAnnexe($annexe->id, $managerRole->id, true);

        $this->actingAs($manager, 'sanctum');

        $this->postJson("/api/admin/users/{$target->id}/assign-role", [
            'annexe_id' => $annexe->id,
            'role_id' => $managerRole->id,
        ])->assertForbidden();
    }
}
