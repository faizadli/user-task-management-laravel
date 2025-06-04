<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use Laravel\Sanctum\Sanctum;
use Illuminate\Support\Facades\Log;
use Illuminate\Foundation\Testing\RefreshDatabase;

class LogRequestTest extends TestCase
{
    use RefreshDatabase;

    public function test_api_requests_are_logged_to_correct_file()
    {
        $user = User::factory()->create(['role' => 'admin']);
        Sanctum::actingAs($user);

        // Mock log channel
        Log::shouldReceive('channel')
            ->with('api_activity')
            ->andReturnSelf();
            
        Log::shouldReceive('info')
            ->once()
            ->with('API Request', \Mockery::type('array'));

        $response = $this->getJson('/api/users');
        
        $response->assertStatus(200);
    }
}