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
