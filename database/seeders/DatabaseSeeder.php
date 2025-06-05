<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\Task;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Carbon\Carbon;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        // Create admin user
        $admin = User::create([
            'name' => 'Admin User',
            'email' => 'admin@example.com',
            'password' => Hash::make('password'),
            'role' => 'admin',
            'status' => true
        ]);
        
        // Create manager user
        $manager = User::create([
            'name' => 'Manager User',
            'email' => 'manager@example.com',
            'password' => Hash::make('password'),
            'role' => 'manager',
            'status' => true
        ]);
        
        // Create staff user
        $staff = User::create([
            'name' => 'Staff User',
            'email' => 'staff@example.com',
            'password' => Hash::make('password'),
            'role' => 'staff',
            'status' => true
        ]);
        
        // Create inactive user
        User::create([
            'name' => 'Inactive User',
            'email' => 'inactive@example.com',
            'password' => Hash::make('password'),
            'role' => 'staff',
            'status' => false  // Inactive user
        ]);

        // Create overdue tasks
        $this->createOverdueTasks($admin, $manager, $staff);
    }

    private function createOverdueTasks($admin, $manager, $staff)
    {
        // Overdue task 1 - Created by admin, assigned to staff, overdue by 5 days
        Task::create([
            'title' => 'Overdue Task - Database Optimization',
            'description' => 'Optimize database queries for better performance. This task is overdue and needs immediate attention.',
            'assigned_to' => $staff->id,
            'created_by' => $admin->id,
            'status' => 'pending',
            'due_date' => Carbon::now()->subDays(5)->format('Y-m-d')
        ]);

        // Overdue task 2 - Created by manager, assigned to staff, overdue by 3 days
        Task::create([
            'title' => 'Overdue Task - Code Review',
            'description' => 'Review and approve pending pull requests. Task is past due date.',
            'assigned_to' => $staff->id,
            'created_by' => $manager->id,
            'status' => 'in_progress',
            'due_date' => Carbon::now()->subDays(3)->format('Y-m-d')
        ]);

        // Overdue task 3 - Created by admin, assigned to manager, overdue by 7 days
        Task::create([
            'title' => 'Overdue Task - Team Performance Report',
            'description' => 'Prepare monthly team performance report and analysis. Severely overdue.',
            'assigned_to' => $manager->id,
            'created_by' => $admin->id,
            'status' => 'pending',
            'due_date' => Carbon::now()->subDays(7)->format('Y-m-d')
        ]);

        // Overdue task 4 - Created by admin, assigned to staff, overdue by 1 day
        Task::create([
            'title' => 'Overdue Task - Bug Fix Authentication',
            'description' => 'Fix authentication bug reported by users. Recently became overdue.',
            'assigned_to' => $staff->id,
            'created_by' => $admin->id,
            'status' => 'in_progress',
            'due_date' => Carbon::now()->subDays(1)->format('Y-m-d')
        ]);

        // Overdue task 5 - Created by manager, assigned to staff, overdue by 10 days
        Task::create([
            'title' => 'Overdue Task - Documentation Update',
            'description' => 'Update project documentation and API references. Long overdue task.',
            'assigned_to' => $staff->id,
            'created_by' => $manager->id,
            'status' => 'pending',
            'due_date' => Carbon::now()->subDays(10)->format('Y-m-d')
        ]);
    }
}