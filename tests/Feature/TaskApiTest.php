<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Task;
use Tests\TestCase;
use Laravel\Sanctum\Sanctum;
use Illuminate\Foundation\Testing\RefreshDatabase;

class TaskApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_view_assigned_tasks()
    {
        $user = User::factory()->create(['role' => 'staff']);
        Sanctum::actingAs($user);

        $task = Task::factory()->create([
            'assigned_to' => $user->id
        ]);

        $response = $this->getJson('/api/tasks');

        $response->assertStatus(200)
                 ->assertJsonFragment([
                     'id' => $task->id,
                     'title' => $task->title
                 ]);
    }

    public function test_manager_can_view_staff_tasks()
    {
        $manager = User::factory()->create(['role' => 'manager']);
        $staff = User::factory()->create(['role' => 'staff']);
        
        Sanctum::actingAs($manager);

        $task = Task::factory()->create([
            'assigned_to' => $staff->id,
            'created_by' => $manager->id
        ]);

        $response = $this->getJson('/api/tasks');

        $response->assertStatus(200)
                 ->assertJsonFragment([
                     'id' => $task->id,
                     'title' => $task->title
                 ]);
    }

    public function test_admin_can_create_task()
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $staff = User::factory()->create(['role' => 'staff']);
        
        Sanctum::actingAs($admin);

        $taskData = [
            'title' => 'New Task',
            'description' => 'Task description',
            'assigned_to' => $staff->id,
            'status' => 'pending',
            'due_date' => now()->addDays(7)->toDateString()
        ];

        $response = $this->postJson('/api/tasks', $taskData);

        $response->assertStatus(201)
                 ->assertJsonFragment([
                     'title' => 'New Task',
                     'assigned_to' => $staff->id
                 ]);
    }

    public function test_manager_can_assign_task_to_staff_only()
    {
        $manager = User::factory()->create(['role' => 'manager']);
        $staff = User::factory()->create(['role' => 'staff']);
        $admin = User::factory()->create(['role' => 'admin']);
        
        Sanctum::actingAs($manager);

        // Should succeed - assign to staff
        $taskData = [
            'title' => 'Task for Staff',
            'description' => 'Description',
            'assigned_to' => $staff->id,
            'status' => 'pending',
            'due_date' => now()->addDays(7)->toDateString()
        ];

        $response = $this->postJson('/api/tasks', $taskData);
        $response->assertStatus(201);

        // Should fail - assign to admin
        $taskData['assigned_to'] = $admin->id;
        $response = $this->postJson('/api/tasks', $taskData);
        $response->assertStatus(422);
    }

    public function test_staff_cannot_create_tasks()
    {
        $staff = User::factory()->create(['role' => 'staff']);
        Sanctum::actingAs($staff);

        $taskData = [
            'title' => 'New Task',
            'description' => 'Description',
            'assigned_to' => $staff->id,
            'status' => 'pending',
            'due_date' => now()->addDays(7)->toDateString()
        ];

        $response = $this->postJson('/api/tasks', $taskData);
        $response->assertStatus(403);
    }

    public function test_user_can_update_own_task()
    {
        $user = User::factory()->create(['role' => 'staff']);
        Sanctum::actingAs($user);

        $task = Task::factory()->create([
            'assigned_to' => $user->id,
            'status' => 'pending'
        ]);

        $response = $this->putJson("/api/tasks/{$task->id}", [
            'status' => 'in_progress'
        ]);

        $response->assertStatus(200)
                 ->assertJsonFragment(['status' => 'in_progress']);
    }

    public function test_admin_can_delete_any_task()
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $user = User::factory()->create(['role' => 'staff']);
        
        Sanctum::actingAs($admin);

        $task = Task::factory()->create(['assigned_to' => $user->id]);

        $response = $this->deleteJson("/api/tasks/{$task->id}");
        $response->assertStatus(200);
    }

    public function test_user_cannot_delete_others_task()
    {
        $user1 = User::factory()->create(['role' => 'staff']);
        $user2 = User::factory()->create(['role' => 'staff']);
        
        Sanctum::actingAs($user1);

        $task = Task::factory()->create(['assigned_to' => $user2->id]);

        $response = $this->deleteJson("/api/tasks/{$task->id}");
        $response->assertStatus(403);
    }

    public function test_task_validation()
    {
        $admin = User::factory()->create(['role' => 'admin']);
        Sanctum::actingAs($admin);

        // Test missing required fields
        $response = $this->postJson('/api/tasks', []);
        $response->assertStatus(422);

        // Test invalid due date
        $response = $this->postJson('/api/tasks', [
            'title' => 'Test Task',
            'description' => 'Description',
            'assigned_to' => $admin->id,
            'status' => 'pending',
            'due_date' => 'invalid-date'
        ]);
        $response->assertStatus(422);
    }

    public function test_inactive_user_cannot_login()
    {
        $user = User::factory()->create([
            'status' => false,
            'password' => bcrypt('password')
        ]);

        $response = $this->postJson('/api/login', [
            'email' => $user->email,
            'password' => 'password'
        ]);

        $response->assertStatus(403);
    }
}