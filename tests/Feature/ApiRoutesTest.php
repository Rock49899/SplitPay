<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Hash;
use App\Models\User;
use App\Models\Student;

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

        $institution = Institution::factory()->create();

        $annexe = Annexe::factory()->create([
        'institution_id' => $institution->id,
       ]);


        $response = $this->postJson('/api/register', $payload);
        $response->assertStatus(201);

        // Response should contain institution/annexe/user (adjust keys if controller differs)
        $response->assertJsonStructure([
            'message',
            'institution',
            'annexe',
            'user',
        ]);
        $user->assignToAnnexe($annexe->id, $role->id, true);
    }

    public function test_admin_login_and_me_and_protected_create_student()
    {
        // Create admin user directly
        $admin = User::create([
            'id' => (string) Str::uuid(),
            'name' => 'Admin Test',
            'email' => 'admin.test@example.com',
            'password' => Hash::make('secret123'),
            'scope' => 'institution',
            'is_active' => true,
        ]);

        // login
        $loginResp = $this->postJson('/api/admin/login', [
            'email' => 'admin.test@example.com',
            'password' => 'secret123',
        ]);

        $loginResp->assertStatus(200);
        $token = $loginResp->json('token') ?? null;

        // if token returned, use it; else try actingAs (sanctum)
        if ($token) {
            $meResp = $this->withHeader('Authorization', 'Bearer '.$token)
                           ->getJson('/api/admin/me');
            $meResp->assertStatus(200)->assertJsonFragment(['email' => 'admin.test@example.com']);
        } else {
            // fallback: act as user (sanctum)
            $this->actingAs($admin, 'sanctum');
            $meResp = $this->getJson('/api/admin/me');
            $meResp->assertStatus(200)->assertJsonFragment(['email' => 'admin.test@example.com']);
        }

        // Create a student via protected endpoint
        $studentPayload = [
            'matricule' => 'MATTEST001',
            'first_name' => 'Jean',
            'last_name' => 'Dupont',
            'email' => 'jean.dupont@example.com',
            'phone' => '0612345678',
            'class' => 'L1',
            'school_year' => '2024-2025',
            'tuition_amount' => 5000,
        ];

        if (isset($token) && $token) {
            $createResp = $this->withHeader('Authorization', 'Bearer '.$token)
                               ->postJson('/api/admin/students', $studentPayload);
        } else {
            $createResp = $this->actingAs($admin, 'sanctum')
                               ->postJson('/api/admin/students', $studentPayload);
        }

        $createResp->assertStatus(201)->assertJsonStructure(['message','student']);
        $this->assertDatabaseHas('students', ['matricule' => 'MATTEST001']);
    }
}
