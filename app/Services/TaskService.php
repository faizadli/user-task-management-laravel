<?php

namespace App\Services;

use App\Models\Task;
use App\Models\User;
use App\Models\ActivityLog;
use Carbon\Carbon;

class TaskService
{
    public function getTasksForUser(User $user)
    {
        $query = Task::query();
        
        // Admin bisa melihat semua task
        if ($user->role !== 'admin') {
            // Non-admin hanya bisa melihat task yang di-assign kepada mereka atau yang mereka buat
            $query->where(function($q) use ($user) {
                $q->where('assigned_to', $user->id)
                  ->orWhere('created_by', $user->id);
            });
        }
        
        $tasks = $query->with(['assignedUser:id,name,email,role', 'creator:id,name,email,role'])->get();
        
        // Debug: Log untuk memeriksa relasi
        foreach ($tasks as $task) {
            \Log::info("Task {$task->id}: assigned_to={$task->assigned_to}, assignedUser=" . ($task->assignedUser ? $task->assignedUser->name : 'NULL'));
        }
        
        return $tasks;
    }
    
    public function validateTaskAssignment(User $creator, $assignedToId)
    {
        $assignedUser = User::find($assignedToId);
        
        if (!$assignedUser) {
            throw new \Exception('Assigned user not found');
        }
        
        // Admin bisa assign ke semua role
        if ($creator->role === 'admin') {
            return $assignedUser;
        }
        
        // Manager bisa assign ke staff dan diri sendiri
        if ($creator->role === 'manager') {
            if ($assignedUser->role === 'staff' || $assignedUser->id === $creator->id) {
                return $assignedUser;
            }
            throw new \Exception('Managers can only assign tasks to staff or themselves');
        }
        
        // Staff bisa create tasks tapi hanya bisa assign ke diri sendiri
        if ($creator->role === 'staff') {
            if ($assignedUser->id === $creator->id) {
                return $assignedUser;
            }
            throw new \Exception('Staff can only assign tasks to themselves');
        }
        
        return $assignedUser;
    }
    
    public function checkOverdueTasks()
    {
        $overdueTasks = Task::where('due_date', '<', Carbon::now())
                           ->where('status', '!=', 'done')
                           ->get();
        
        foreach ($overdueTasks as $task) {
            ActivityLog::create([
                'user_id' => $task->created_by,
                'action' => 'task_overdue',
                'description' => "Task overdue: {$task->id}",
                'logged_at' => now()
            ]);
        }
        
        return $overdueTasks->count();
    }
    
    /**
     * Get all tasks that are overdue and not completed
     * 
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function getOverdueTasks()
    {
        return Task::where('due_date', '<', Carbon::now())
                   ->where('status', '!=', 'done')
                   ->get();
    }
    
    /**
     * Check if a user can assign a task to another user
     * 
     * @param User $assigner The user assigning the task
     * @param User $assignee The user being assigned to
     * @return bool
     */
    public function canAssignTask(User $assigner, User $assignee)
    {
        // Admin can assign to anyone
        if ($assigner->role === 'admin') {
            return true;
        }
        
        // Manager can assign to staff or themselves
        if ($assigner->role === 'manager') {
            return $assignee->role === 'staff' || $assignee->id === $assigner->id;
        }
        
        // Staff can only assign to themselves
        if ($assigner->role === 'staff') {
            return $assignee->id === $assigner->id;
        }
        
        return false;
    }
    
    /**
     * Check if a status transition is valid
     * 
     * @param string $currentStatus Current task status
     * @param string $newStatus New task status
     * @return bool
     */
    public function isValidStatusTransition(string $currentStatus, string $newStatus)
    {
        $validTransitions = [
            'pending' => ['in_progress', 'cancelled'],
            'in_progress' => ['done', 'cancelled'],
            'done' => ['cancelled'],
            'cancelled' => []
        ];
        
        // If current status doesn't exist in our valid transitions map
        if (!isset($validTransitions[$currentStatus])) {
            return false;
        }
        
        // Check if the new status is in the list of valid transitions
        return in_array($newStatus, $validTransitions[$currentStatus]);
    }
}