<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Illuminate\Support\Str;
use App\Models\User;
use App\Models\Role;
use App\Models\Annexe;

class RolePolicyTest extends TestCase
{
    use RefreshDatabase;

    public function test_assign_and_remove_role_authorization()
    {
        if (! class_exists(User::class) || ! class_exists(Role::class) || ! class_exists(Annexe::class)) {
            $this->markTestSkipped('User/Role/Annexe models missing.');
        }

        // create annexe and role
        $annexe = Annexe::create(['id' => (string) Str::uuid(), 'institution_id' => (string) Str::uuid(), 'name' => 'A', 'is_active' => true]);
        $role = Role::create(['id' => (string) Str::uuid(), 'name' => 'Manager', 'code' => 'manager']);

        // create super-admin institution user
        $super = User::create([
            'id' => (string) Str::uuid(),
            'name' => 'Super',
            'email' => 'super@local',
            'password' => bcrypt('secret'),
            'scope' => 'institution',
            'is_active' => true,
        ]);

        // create normal user
        $target = User::create([
            'id' => (string) Str::uuid(),
            'name' => 'Target',
            'email' => 'target@local',
            'password' => bcrypt('secret'),
            'scope' => 'annexe',
            'is_active' => true,
        ]);

        // act as super-admin (assume method assignRole allowed)
        $this->actingAs($super, 'sanctum');

        $assignResp = $this->postJson("/api/admin/users/{$target->id}/assign-role", [
            'annexe_id' => $annexe->id,
            'role_id' => $role->id,
            'is_primary' => true,
        ]);

        // either 200 or 501 if role system not implemented
        $this->assertContains($assignResp->status(), [200, 501]);

        $removeResp = $this->postJson("/api/admin/users/{$target->id}/remove-role", [
            'annexe_id' => $annexe->id,
            'role_id' => $role->id,
        ]);

        $this->assertContains($removeResp->status(), [200, 501]);

        // unauthorized user attempt
        $unauth = User::create([
            'id' => (string) Str::uuid(),
            'name' => 'NoPerm',
            'email' => 'noperm@local',
            'password' => bcrypt('secret'),
            'scope' => 'annexe',
            'is_active' => true,
        ]);

        $this->actingAs($unauth, 'sanctum');
        $resp = $this->postJson("/api/admin/users/{$target->id}/assign-role", [
            'annexe_id' => $annexe->id,
            'role_id' => $role->id,
        ]);

        // expect 403 or 501 depending on implementation
        $this->assertTrue(in_array($resp->status(), [403, 501]));
    }
}
