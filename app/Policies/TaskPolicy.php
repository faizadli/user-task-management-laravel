<?php

namespace App\Policies;

use App\Models\Task;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class TaskPolicy
{
    use HandlesAuthorization;

    public function viewAny(User $user)
    {
        return true; // Semua role bisa melihat tasks (dengan filter di controller)
    }

    public function view(User $user, Task $task)
    {
        if ($user->role === 'admin') {
            return true;
        }
        
        if ($user->role === 'manager') {
            // Manager bisa melihat task yang dibuat atau ditugaskan ke staff
            return $task->created_by === $user->id || 
                   ($task->assignedUser && $task->assignedUser->role === 'staff');
        }
        
        if ($user->role === 'staff') {
            // Staff hanya bisa melihat task yang ditugaskan atau dibuat olehnya
            return $task->assigned_to === $user->id || $task->created_by === $user->id;
        }
        
        return false;
    }

    public function create(User $user)
    {
        // Staff cannot create tasks
        return $user->role !== 'staff';
    }

    public function update(User $user, Task $task)
    {
        if ($user->role === 'admin') {
            return true;
        }
        
        if ($user->role === 'manager') {
            return $task->created_by === $user->id;
        }
        
        if ($user->role === 'staff') {
            return $task->assigned_to === $user->id;
        }
        
        return false;
    }

    public function delete(User $user, Task $task)
    {
        return $user->role === 'admin' || $task->created_by === $user->id;
    }
}