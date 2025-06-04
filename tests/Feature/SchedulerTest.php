<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\Task;
use App\Models\User;
use App\Models\ActivityLog;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;

class SchedulerTest extends TestCase
{
    use RefreshDatabase;

    public function test_overdue_task_checker_creates_activity_logs()
    {
        $user = User::factory()->create();
        
        // Buat task yang overdue
        $overdueTask = Task::factory()->create([
            'due_date' => Carbon::yesterday(),
            'status' => 'pending',
            'created_by' => $user->id,
            'assigned_to' => $user->id
        ]);
        
        // Buat task yang tidak overdue
        $normalTask = Task::factory()->create([
            'due_date' => Carbon::tomorrow(),
            'status' => 'pending',
            'created_by' => $user->id,
            'assigned_to' => $user->id
        ]);
        
        // Jalankan command
        Artisan::call('tasks:check-overdue');
        
        // Periksa activity log
        $this->assertDatabaseHas('activity_logs', [
            'action' => 'task_overdue',
            'description' => "Task overdue: {$overdueTask->id}"
        ]);
        
        // Pastikan task normal tidak masuk log
        $this->assertDatabaseMissing('activity_logs', [
            'description' => "Task overdue: {$normalTask->id}"
        ]);
    }
}