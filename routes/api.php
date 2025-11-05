<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\OpdController;
use App\Http\Controllers\Api\DesaController;
use App\Http\Controllers\Api\KecamatanController;


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

<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\DesaController;
use App\Http\Controllers\Api\KecamatanController;
use App\Http\Controllers\Api\UsulanController;


// API Kecamatan

Route::apiResource('kecamatan', KecamatanController::class);
Route::get('kecamatan-search', [KecamatanController::class, 'search']);


// API Desa

Route::apiResource('desa', DesaController::class);
// Additional routes untuk Desa
Route::get('/desa-kecamatan/{idKecamatan}', [DesaController::class, 'getByKecamatan']);
Route::get('/desa-search', [DesaController::class, 'search']);
Route::get('/desa-paginated', [DesaController::class, 'paginated']);

Route::apiResource('usulan', UsulanController::class);
Route::get('/log-usulan', [UsulanController::class, 'getLogs']);
