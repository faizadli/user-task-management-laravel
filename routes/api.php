<?php

use App\Http\Controllers\ActivityLogController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\TaskController;
use Illuminate\Support\Facades\Route;

// Login dengan middleware logRequest
Route::post('/login', [AuthController::class, 'login'])->middleware('logRequest');

Route::middleware(['auth:sanctum', 'checkUserStatus', 'logRequest'])->group(function () {
    Route::post('/logout', [AuthController::class, 'logout']);
    
    // User routes
    Route::get('/users', [UserController::class, 'index']);
    Route::post('/users', [UserController::class, 'store']);
    
    // Task routes
    Route::get('/tasks', [TaskController::class, 'index']);
    Route::get('/tasks/export', [TaskController::class, 'exportCsv']); // Pindahkan ke sini
    Route::get('/tasks/{task}', [TaskController::class, 'show']);
    Route::post('/tasks', [TaskController::class, 'store']);
    Route::put('/tasks/{task}', [TaskController::class, 'update']);
    Route::delete('/tasks/{task}', [TaskController::class, 'destroy']);

    // Logs routes
    Route::get('/logs', [ActivityLogController::class, 'index'])->middleware('can:viewAny,App\\Models\\ActivityLog');
});