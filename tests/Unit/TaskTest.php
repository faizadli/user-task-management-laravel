<?php

namespace Tests\Unit;

use App\Models\User;
use App\Models\Task;
use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;

class TaskTest extends TestCase
{
    use RefreshDatabase;

    public function test_manager_can_only_assign_tasks_to_staff()
    {
        $manager = User::factory()->create(['role' => 'manager']);
        $staff = User::factory()->create(['role' => 'staff']);
        $anotherManager = User::factory()->create(['role' => 'manager']);

        $taskData = [
            'title' => 'Test Task',
            'description' => 'Test Description',
            'status' => 'pending',
            'due_date' => now()->addDays(1)->toDateString(),
            'created_by' => $manager->id
        ];

        // Manager assigning to staff should succeed
        $taskData['assigned_to'] = $staff->id;
        $task = Task::create($taskData);
        $this->assertEquals($staff->id, $task->assigned_to);

        // Manager assigning to another manager should fail
        $this->expectException(\Exception::class);
        $taskData['assigned_to'] = $anotherManager->id;
        Task::create($taskData);
    }

    public function test_staff_cannot_create_tasks()
    {
        $staff = User::factory()->create(['role' => 'staff']);
        $anotherStaff = User::factory()->create(['role' => 'staff']);

        $this->expectException(\Exception::class);
        
        Task::create([
            'title' => 'Test Task',
            'description' => 'Test Description',
            'assigned_to' => $anotherStaff->id,
            'status' => 'pending',
            'due_date' => now()->addDays(1)->toDateString(),
            'created_by' => $staff->id
        ]);
    }
}