<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\UserController;
use Illuminate\Support\Facades\Route;

// Public routes
Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login']);

// Protected routes (using Sanctum)
Route::middleware('auth:sanctum')->group(function () {
    // Auth routes
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::get('/user-profile', [AuthController::class, 'me']);
    
    // User profile routes
    Route::get('/profile', [UserController::class, 'showProfile']);
    Route::put('/profile', [UserController::class, 'updateProfile']);
    Route::post('/change-password', [UserController::class, 'changePassword']);
    
    // ========== SPECIFIC ROUTES FIRST (without parameters) ==========
    
    // Admin statistics and activity - MUST BE BEFORE /users/{id}
    Route::get('/users/statistics', [UserController::class, 'getStatistics']);
    Route::get('/users/activity', [UserController::class, 'getUserActivity']);
    Route::post('/users/search', [UserController::class, 'searchUsers']);
    Route::put('/users/bulk-update-roles', [UserController::class, 'bulkUpdateRoles']); // ⬅️ CHANGED TO PUT
    Route::get('/users/export', [UserController::class, 'exportUsers']);
    
    // Admin routes without parameters
    Route::get('/users', [UserController::class, 'index']);
    Route::post('/create-admin', [AuthController::class, 'createAdmin']);
    
    // ========== PARAMETERIZED ROUTES LAST ==========
    
    // Routes with {id} parameter - MUST BE LAST
    Route::post('/users/{id}/change-role', [UserController::class, 'changeRole']);
    Route::put('/users/{id}', [UserController::class, 'update']);
    Route::delete('/users/{id}', [UserController::class, 'destroy']);
    
    // ⚠️ THIS MUST BE THE VERY LAST ROUTE ⚠️
    Route::get('/users/{id}', [UserController::class, 'show']);
});