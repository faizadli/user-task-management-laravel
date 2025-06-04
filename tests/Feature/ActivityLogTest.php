<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\ActivityLog;
use Tests\TestCase;
use Laravel\Sanctum\Sanctum;
use Illuminate\Foundation\Testing\RefreshDatabase;

class ActivityLogTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_view_logs()
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $user = User::factory()->create();
        
        // Create some activity logs
        ActivityLog::factory()->create([
            'user_id' => $user->id,
            'action' => 'create_task',
            'description' => 'Created a new task'
        ]);

        Sanctum::actingAs($admin);

        $response = $this->getJson('/api/logs');

        $response->assertStatus(200)
                 ->assertJsonStructure([
                     'data' => [
                         '*' => ['id', 'user_id', 'action', 'description', 'logged_at']
                     ]
                 ]);
    }

    public function test_manager_cannot_view_logs()
    {
        $manager = User::factory()->create(['role' => 'manager']);
        Sanctum::actingAs($manager);

        $response = $this->getJson('/api/logs');

        $response->assertStatus(403);
    }

    public function test_staff_cannot_view_logs()
    {
        $staff = User::factory()->create(['role' => 'staff']);
        Sanctum::actingAs($staff);

        $response = $this->getJson('/api/logs');

        $response->assertStatus(403);
    }
}