<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Hash;
use App\Models\Annexe;
use App\Models\Institution;
use App\Models\Role;
use App\Models\User;

class ApiRoutesTest extends TestCase
{
    use RefreshDatabase;

    public function test_ping_returns_pong()
    {
        $response = $this->getJson('/api/ping');
        $response->assertStatus(200)->assertSeeText('pong');
    }

    public function test_register_creates_institution_annexe_and_user()
    {
        $payload = [
            'institution_name' => 'Test Institution',
            'institution_email' => 'inst@example.com',
            'institution_phone' => '0123456789',
            'annexe_name' => 'Main Campus',
            'owner_name' => 'Admin User',
            'owner_email' => 'admin@example.com',
            'owner_password' => 'Password123!',
        ];

        $response = $this->postJson('/api/register', $payload);
        $response->assertStatus(201);
        $response->assertJsonStructure([
            'message',
            'institution',
            'annexe',
            'user',
        ]);

        $this->assertDatabaseHas('institutions', ['name' => 'Test Institution']);
        $this->assertDatabaseHas('annexes', ['name' => 'Main Campus']);
        $this->assertDatabaseHas('users', ['email' => 'admin@example.com']);
    }

    public function test_admin_login_and_me()
    {
        $institution = Institution::create([
            'id' => (string) Str::uuid(),
            'name' => 'Institution Test',
            'email' => 'institution@test.local',
            'phone' => '0102030405',
            'is_active' => true,
        ]);

        $annexe = Annexe::create([
            'id' => (string) Str::uuid(),
            'institution_id' => $institution->id,
            'name' => 'Annexe Principale',
            'is_active' => true,
        ]);

        $role = Role::create([
            'id' => (string) Str::uuid(),
            'code' => 'super_admin_institution',
            'label' => 'Super admin institution',
            'scope' => 'institution',
        ]);

        $admin = User::create([
            'id' => (string) Str::uuid(),
            'name' => 'Admin Test',
            'email' => 'admin.test@example.com',
            'password' => Hash::make('secret123'),
            'scope' => 'institution',
            'is_active' => true,
            'annexe_id' => $annexe->id,
        ]);

        $admin->assignToAnnexe($annexe->id, $role->id, true);

        $loginResp = $this->postJson('/api/admin/login', [
            'email' => 'admin.test@example.com',
            'password' => 'secret123',
        ]);

        $loginResp->assertStatus(200)->assertJsonStructure(['token', 'user']);

        $token = $loginResp->json('token');
        $this->assertNotEmpty($token);

        $meResp = $this->withHeader('Authorization', 'Bearer ' . $token)
            ->getJson('/api/admin/me');

        $meResp->assertStatus(200)
            ->assertJsonPath('user.email', 'admin.test@example.com');
    }
}
