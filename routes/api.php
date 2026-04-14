<?php

use App\Http\Controllers\Api\AdminController;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\TagController;
use App\Http\Controllers\Api\TaskController;
use Illuminate\Support\Facades\Route;

Route::post('/auth/google', [AuthController::class, 'google'])->middleware('throttle:10,1');

Route::middleware(['auth:sanctum', 'throttle:60,1'])->group(function () {
    Route::get('/me', [AuthController::class, 'me']);
    Route::post('/logout', [AuthController::class, 'logout']);

    Route::get('/tasks', [TaskController::class, 'index']);
    Route::post('/tasks', [TaskController::class, 'store']);
    Route::get('/tasks/{id}', [TaskController::class, 'show']);
    Route::put('/tasks/{id}', [TaskController::class, 'update']);
    Route::delete('/tasks/{id}', [TaskController::class, 'destroy']);

    Route::post('/tasks/{id}/tags', [TaskController::class, 'attachTags']);
    Route::delete('/tasks/{taskId}/tags/{tagId}', [TaskController::class, 'detachTag']);

    Route::get('/tags', [TagController::class, 'index']);

    Route::get('/admin/users', [AdminController::class, 'listUsers']);
    Route::post('/admin/users', [AdminController::class, 'createUser']);
    Route::patch('/admin/users/{user}', [AdminController::class, 'updateUser']);
    Route::patch('/admin/users/{user}/toggle-active', [AdminController::class, 'toggleUser']);
});
