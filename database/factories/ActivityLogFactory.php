<?php

namespace Database\Factories;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class ActivityLogFactory extends Factory
{
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'action' => $this->faker->randomElement([
                'create_user', 'update_user', 'delete_user',
                'create_task', 'update_task', 'delete_task',
                'login', 'logout'
            ]),
            'description' => $this->faker->sentence(),
            'logged_at' => $this->faker->dateTimeThisMonth(),
        ];
    }
}