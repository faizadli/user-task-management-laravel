<?php

namespace Tests\Feature;

use App\Models\User;
use Tests\TestCase;
use Laravel\Sanctum\Sanctum;
use Illuminate\Foundation\Testing\RefreshDatabase;

class UserApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_create_user()
    {
        $admin = User::factory()->create(['role' => 'admin']);
        Sanctum::actingAs($admin);

        $userData = [
            'name' => 'New User',
            'email' => 'newuser@example.com',
            'password' => 'password123',
            'role' => 'staff',
            'status' => true
        ];

        $response = $this->postJson('/api/users', $userData);

        $response->assertStatus(201)
                 ->assertJsonFragment([
                     'name' => 'New User',
                     'email' => 'newuser@example.com',
                     'role' => 'staff'
                 ]);
    }

    public function test_admin_can_view_all_users()
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $staff = User::factory()->create(['role' => 'staff']);
        $manager = User::factory()->create(['role' => 'manager']);
        
        Sanctum::actingAs($admin);

        $response = $this->getJson('/api/users');

        $response->assertStatus(200)
                 ->assertJsonCount(3, 'data');
    }

    public function test_manager_can_view_users()
    {
        $manager = User::factory()->create(['role' => 'manager']);
        $staff = User::factory()->create(['role' => 'staff']);
        
        Sanctum::actingAs($manager);

        $response = $this->getJson('/api/users');

        $response->assertStatus(200);
    }

    public function test_staff_cannot_view_users()
    {
        $staff = User::factory()->create(['role' => 'staff']);
        Sanctum::actingAs($staff);

        $response = $this->getJson('/api/users');

        $response->assertStatus(403);
    }

    public function test_non_admin_cannot_create_user()
    {
        $manager = User::factory()->create(['role' => 'manager']);
        Sanctum::actingAs($manager);

        $userData = [
            'name' => 'New User',
            'email' => 'newuser@example.com',
            'password' => 'password123',
            'role' => 'staff'
        ];

        $response = $this->postJson('/api/users', $userData);

        $response->assertStatus(403);
    }

    public function test_create_user_validation()
    {
        $admin = User::factory()->create(['role' => 'admin']);
        Sanctum::actingAs($admin);

        // Test missing required fields
        $response = $this->postJson('/api/users', []);
        $response->assertStatus(422);

        // Test duplicate email
        $existingUser = User::factory()->create();
        $response = $this->postJson('/api/users', [
            'name' => 'Test User',
            'email' => $existingUser->email,
            'password' => 'password123',
            'role' => 'staff'
        ]);
        $response->assertStatus(422);
    }
}