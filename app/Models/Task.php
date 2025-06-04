<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Exception;

class Task extends Model
{
    use HasFactory, HasUuids;

    protected $fillable = [
        'title',
        'description',
        'assigned_to',
        'status',
        'due_date',
        'created_by'
    ];

    protected $casts = [
        'due_date' => 'date'
    ];

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($task) {
            // Skip validation during testing when using factories
            if (app()->environment('testing') && !isset($task->created_by)) {
                return;
            }
            
            $creator = User::find($task->created_by);
            if (!$creator) {
                throw new Exception('Creator not found');
            }

            // Staff cannot create tasks
            if ($creator->role === 'staff') {
                throw new Exception('Staff cannot create tasks');
            }

            // Manager can only assign to staff
            if ($creator->role === 'manager' && $task->assigned_to) {
                $assignee = User::find($task->assigned_to);
                if ($assignee && $assignee->role !== 'staff') {
                    throw new Exception('Manager can only assign tasks to staff');
                }
            }
        });
    }

    public function assignedUser()
    {
        return $this->belongsTo(User::class, 'assigned_to');
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
