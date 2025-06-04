<?php

namespace Database\Factories;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class TaskFactory extends Factory
{
    public function definition(): array
    {
        return [
            'title' => $this->faker->sentence(4),
            'description' => $this->faker->paragraph(),
            'assigned_to' => User::factory(),
            'created_by' => User::factory()->create(['role' => 'admin']), // Gunakan admin untuk menghindari validasi
            'status' => $this->faker->randomElement(['pending', 'in_progress', 'done']),
            'due_date' => $this->faker->dateTimeBetween('now', '+30 days')->format('Y-m-d'),
        ];
    }
}