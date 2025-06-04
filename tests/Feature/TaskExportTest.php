<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use App\Models\Task;
use Laravel\Sanctum\Sanctum;
use Illuminate\Foundation\Testing\RefreshDatabase;

class TaskExportTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_export_tasks_csv()
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $task = Task::factory()->create();
        
        Sanctum::actingAs($admin);
        
        $response = $this->get('/api/tasks/export');
        
        $response->assertStatus(200)
                 ->assertHeader('content-type', 'text/csv; charset=UTF-8');
                 
        // Test content-disposition header secara terpisah
        $contentDisposition = $response->headers->get('content-disposition');
        $this->assertStringContainsString('attachment; filename="tasks_', $contentDisposition);
    }
    
    public function test_staff_cannot_export_all_tasks()
    {
        $staff = User::factory()->create(['role' => 'staff']);
        Sanctum::actingAs($staff);
        
        $response = $this->get('/api/tasks/export');
        
        $response->assertStatus(403);
    }
}