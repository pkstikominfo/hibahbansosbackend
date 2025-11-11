<?php

use App\Models\Spj;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\OpdController;
use App\Http\Controllers\Api\SpjController;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\DesaController;
use App\Http\Controllers\Api\UserController;
use App\Http\Controllers\Api\UsulanController;
use App\Http\Controllers\Api\KecamatanController;
use App\Http\Controllers\Api\StatistikController;


// Public routes (tanpa authentication)
Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login']);


// API Kecamatan
Route::apiResource('kecamatan', KecamatanController::class);
Route::get('kecamatan-search', [KecamatanController::class, 'search']);


// API Desa
Route::apiResource('desa', DesaController::class);
// Additional routes untuk Desa
Route::get('/desa-kecamatan/{idKecamatan}', [DesaController::class, 'getByKecamatan']);
Route::get('/desa-search', [DesaController::class, 'search']);
Route::get('/desa-paginated', [DesaController::class, 'paginated']);


// Routes untuk OPD
Route::apiResource('opd', OpdController::class);
// Additional routes untuk OPD
Route::get('/opd-search', [OpdController::class, 'search']);
Route::get('/opd-with-users-count', [OpdController::class, 'withUsersCount']);
Route::get('/opd-paginated', [OpdController::class, 'paginated']);

// Routes untuk User
Route::apiResource('users', UserController::class);
// Additional routes untuk User
Route::get('/users-search', [UserController::class, 'search']);
Route::get('/users-by-role/{role}', [UserController::class, 'getByRole']);
Route::get('/users-paginated', [UserController::class, 'paginated']);
Route::put('/users/{id}/status', [UserController::class, 'updateStatus']);

// Routes untuk Usulan
Route::get('/log-usulan', [UsulanController::class, 'getLogs']);
// API Usulan
Route::apiResource('usulan', UsulanController::class);
Route::apiResource('spj', SpjController::class);

Route::get('/feed-bantuan', [SpjController::class, 'feedBantuan']);
Route::get('/detail-bantuan', [SpjController::class, 'detailBantuan']);
Route::get('/log-bantuan', [UsulanController::class, 'getLogs']);
Route::get('/statistik', [StatistikController::class, 'getStatistik']);
Route::get('/sebaran-data', [UsulanController::class, 'getSebaranAnggaranDisetujui']);

// Protected routes (perlu authentication)
Route::middleware('auth:sanctum')->group(function () {
    // Auth routes
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::get('/user', [AuthController::class, 'user']);
    Route::put('/user/profile', [AuthController::class, 'updateProfile']);
    Route::put('/user/change-password', [AuthController::class, 'changePassword']);

    Route::prefix('usulan')->group(function () {
        // Basic CRUD
        Route::get('/', [UsulanController::class, 'index']);
        Route::post('/', [UsulanController::class, 'store']);
        Route::get('/{id}', [UsulanController::class, 'show']);
        Route::put('/{id}', [UsulanController::class, 'update']);
        Route::delete('/{id}', [UsulanController::class, 'destroy']);

        // Special actions
        Route::post('/{id}/assign', [UsulanController::class, 'assignOpd']);
        Route::post('/{id}/approve', [UsulanController::class, 'approve']);

        // Logs
        Route::get('/logs/all', [UsulanController::class, 'getLogs']);
    });
});
