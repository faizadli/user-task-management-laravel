<?php

namespace App\Http\Controllers;

use App\Models\Task;
use App\Models\User;
use App\Models\ActivityLog;
use Illuminate\Http\Request;
use App\Services\TaskService;
use Illuminate\Support\Facades\Gate;

class TaskController extends Controller
{

    protected $taskService;

    public function __construct(TaskService $taskService)
    {
        $this->taskService = $taskService;
    }

    public function index(Request $request)
    {
        try {
            $this->authorize('viewAny', Task::class);
            
            $tasks = $this->taskService->getTasksForUser($request->user());
            
            // Debug: Log untuk memeriksa data
            \Log::info('Tasks data:', $tasks->toArray());
            
            return response()->json($tasks);
        } catch (\Exception $e) {
            \Log::error('Task index error: ' . $e->getMessage());
            return response()->json([
                'message' => 'Error fetching tasks',
                'error' => $e->getMessage()
            ], 500);
        }
    }
    
    public function store(Request $request)
    {
        // Check if user can create tasks first
        $this->authorize('create', Task::class);
        
        $validated = $request->validate([
            'title' => 'required|string',
            'description' => 'required|string',
            'assigned_to' => 'required|exists:users,id',
            'status' => 'required|in:pending,in_progress,done',
            'due_date' => 'required|date'
        ]);
        
        // Additional validation for manager role
        if ($request->user()->role === 'manager') {
            $assignedUser = User::find($validated['assigned_to']);
            if ($assignedUser->role !== 'staff') {
                return response()->json([
                    'message' => 'Manager can only assign tasks to staff'
                ], 422);
            }
        }
        
        try {
            $validated['created_by'] = $request->user()->id;
            $task = Task::create($validated);
            
            ActivityLog::create([
                'user_id' => $request->user()->id,
                'action' => 'create_task',
                'description' => "Created task: {$task->title}",
                'logged_at' => now()
            ]);
            
            return response()->json($task->load(['assignedUser', 'creator']), 201);
        } catch (\Exception $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }
    }

    public function update(Request $request, Task $task)
    {
        $user = $request->user();
        
        // Admin bisa update semua task
        if ($user->role === 'admin') {
            // Admin allowed
        }
        // Semua user bisa update task yang di-assign kepada mereka atau yang mereka buat
        elseif ($task->assigned_to === $user->id || $task->created_by === $user->id) {
            // User allowed
        }
        else {
            return response()->json(['message' => 'Unauthorized'], 403);
        }
    
        $validationRules = [
            'title' => 'string',
            'description' => 'string',
            'status' => 'in:pending,in_progress,done',
            'due_date' => 'date'
        ];
        
        // Only admin and manager can update assigned_to
        if (in_array($user->role, ['admin', 'manager'])) {
            $validationRules['assigned_to'] = 'exists:users,id';
        }
    
        $validated = $request->validate($validationRules);
        
        // If assigned_to is being changed, validate the assignment
        if (isset($validated['assigned_to']) && $validated['assigned_to'] !== $task->assigned_to) {
            try {
                $this->taskService->validateTaskAssignment($user, $validated['assigned_to']);
            } catch (\Exception $e) {
                return response()->json(['message' => $e->getMessage()], 403);
            }
        }
    
        $task->update($validated);
    
        ActivityLog::create([
            'user_id' => $user->id,
            'action' => 'update_task',
            'description' => "Updated task: {$task->title}",
            'logged_at' => now()
        ]);
    
        return response()->json($task->load(['assignedUser', 'creator']));
    }

    public function destroy(Request $request, Task $task)
    {
        $user = $request->user();

        // Admin bisa delete semua task
        if ($user->role === 'admin') {
            // Admin allowed
        }
        // Semua user bisa delete task yang di-assign kepada mereka atau yang mereka buat
        elseif ($task->assigned_to === $user->id || $task->created_by === $user->id) {
            // User allowed
        }
        else {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        ActivityLog::create([
            'user_id' => $user->id,
            'action' => 'delete_task',
            'description' => "Deleted task: {$task->title}",
            'logged_at' => now()
        ]);

        $task->delete();
        return response()->json(['message' => 'Task deleted successfully'], 200); // Changed from 204 to 200
    }

    public function show(Request $request, Task $task)
    {
        try {
            $user = $request->user();
            
            // Admin bisa melihat semua task
            if ($user->role === 'admin') {
                // Admin allowed
            }
            // Semua user bisa melihat task yang di-assign kepada mereka atau yang mereka buat
            elseif ($task->assigned_to === $user->id || $task->created_by === $user->id) {
                // User allowed
            }
            else {
                return response()->json(['message' => 'Unauthorized'], 403);
            }
            
            return response()->json($task->load(['assignedUser', 'creator']));
        } catch (\Exception $e) {
            \Log::error('Task show error: ' . $e->getMessage());
            return response()->json([
                'message' => 'Error fetching task details',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function exportCsv(Request $request)
    {
        $this->authorize('export', Task::class); // Ganti dari 'viewAny' ke 'export'
        
        $tasks = $this->taskService->getTasksForUser($request->user());
        
        $filename = 'tasks_' . date('Y-m-d_H-i-s') . '.csv';
        
        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename="' . $filename . '"',
        ];
        
        $callback = function() use ($tasks) {
            $file = fopen('php://output', 'w');
            fputcsv($file, ['ID', 'Title', 'Description', 'Status', 'Due Date', 'Assigned To', 'Created By']);
            
            foreach ($tasks as $task) {
                fputcsv($file, [
                    $task->id,
                    $task->title,
                    $task->description,
                    $task->status,
                    $task->due_date,
                    $task->assignedUser->name ?? '',
                    $task->creator->name ?? ''
                ]);
            }
            fclose($file);
        };
        
        return response()->stream($callback, 200, $headers);
    }
}