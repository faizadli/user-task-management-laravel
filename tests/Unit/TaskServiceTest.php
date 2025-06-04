<?php

namespace Tests\Unit;

use App\Models\User;
use App\Models\Task;
use App\Services\TaskService;
use Tests\TestCase;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;

class TaskServiceTest extends TestCase
{
    use RefreshDatabase;

    protected $taskService;

    protected function setUp(): void
    {
        parent::setUp();
        $this->taskService = new TaskService();
    }

    public function test_overdue_task_detection()
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $user = User::factory()->create();
        
        // Create overdue task with proper creator
        $overdueTask = Task::factory()->create([
            'due_date' => Carbon::yesterday(),
            'status' => 'pending',
            'assigned_to' => $user->id,
            'created_by' => $admin->id
        ]);

        // Create non-overdue task with proper creator
        $normalTask = Task::factory()->create([
            'due_date' => Carbon::tomorrow(),
            'status' => 'pending',
            'assigned_to' => $user->id,
            'created_by' => $admin->id
        ]);

        $overdueTasks = $this->taskService->getOverdueTasks();

        $this->assertTrue($overdueTasks->contains($overdueTask));
        $this->assertFalse($overdueTasks->contains($normalTask));
    }

    public function test_role_based_task_filtering()
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $manager = User::factory()->create(['role' => 'manager']);
        $staff = User::factory()->create(['role' => 'staff']);

        $task1 = Task::factory()->create([
            'assigned_to' => $staff->id,
            'created_by' => $admin->id
        ]);
        $task2 = Task::factory()->create([
            'assigned_to' => $manager->id,
            'created_by' => $admin->id
        ]);
        $task3 = Task::factory()->create([
            'created_by' => $admin->id,
            'assigned_to' => $staff->id
        ]);

        // Admin should see all tasks
        $adminTasks = $this->taskService->getTasksForUser($admin);
        $this->assertCount(3, $adminTasks);

        // Staff should only see assigned tasks
        $staffTasks = $this->taskService->getTasksForUser($staff);
        $this->assertTrue($staffTasks->contains($task1));
        $this->assertTrue($staffTasks->contains($task3));
        $this->assertFalse($staffTasks->contains($task2));
    }

    public function test_assignment_validation()
    {
        $manager = User::factory()->create(['role' => 'manager']);
        $staff = User::factory()->create(['role' => 'staff']);
        $admin = User::factory()->create(['role' => 'admin']);

        // Manager can assign to staff
        $this->assertTrue(
            $this->taskService->canAssignTask($manager, $staff)
        );

        // Manager cannot assign to admin
        $this->assertFalse(
            $this->taskService->canAssignTask($manager, $admin)
        );

        // Admin can assign to anyone
        $this->assertTrue(
            $this->taskService->canAssignTask($admin, $manager)
        );
    }

    public function test_task_status_transition_validation()
    {
        $task = Task::factory()->create(['status' => 'pending']);

        // Valid transitions
        $this->assertTrue(
            $this->taskService->isValidStatusTransition('pending', 'in_progress')
        );
        $this->assertTrue(
            $this->taskService->isValidStatusTransition('in_progress', 'done')
        );

        // Invalid transitions
        $this->assertFalse(
            $this->taskService->isValidStatusTransition('done', 'pending')
        );
    }
}