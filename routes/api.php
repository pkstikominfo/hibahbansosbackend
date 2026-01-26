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
use App\Http\Controllers\Api\FilePersyaratanController;
use App\Http\Controllers\Api\UsulanPersyaratanController;
use App\Http\Controllers\Api\SpjPersyaratanController;
use App\Http\Controllers\Api\SubJenisBantuanController;
use App\Http\Controllers\Api\JenisBantuanController;
use App\Http\Controllers\Api\KategoriController;


use App\Http\Controllers\Api\TokenController;



// Public routes (tanpa authentication)
Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login']);



Route::prefix('kecamatan')->group(function () {
    Route::get('/', [KecamatanController::class, 'index']);
    Route::get('/search', [KecamatanController::class, 'search']);
    Route::get('/{id}', [KecamatanController::class, 'show']);
});


Route::prefix('desa')->group(function () {
    Route::get('/', [DesaController::class, 'index']);
    Route::get('/search', [DesaController::class, 'search']);
    Route::get('/paginated', [DesaController::class, 'paginated']);
    Route::get('/kecamatan/{idKecamatan}', [DesaController::class, 'getByKecamatan']);
    Route::get('/coordinates', [DesaController::class, 'getByCoordinates']);
    Route::get('/with-coordinates', [DesaController::class, 'denganKoordinat']);
    Route::get('/{id}', [DesaController::class, 'show']);
});



Route::apiResource('opd', OpdController::class);
Route::get('/opd-search', [OpdController::class, 'search']);
Route::get('/opd-with-users-count', [OpdController::class, 'withUsersCount']);
Route::get('/opd-paginated', [OpdController::class, 'paginated']);




Route::get('/log-usulan', [UsulanController::class, 'getLogs']);
Route::apiResource('spj', SpjController::class);
Route::put('/spj/{id}/status', [SpjController::class, 'updateStatus']);
Route::get('spj/getByOpd/{kode_opd}', [SpjController::class, 'getByOpd']);
Route::get('/feed-bantuan', [SpjController::class, 'feedBantuan']);
Route::get('/detail-bantuan', [SpjController::class, 'detailBantuan']);
Route::get('/log-bantuan', [UsulanController::class, 'getLogs']);
Route::get('/statistik', [StatistikController::class, 'getStatistik']);
Route::get('/sebaran-data', [UsulanController::class, 'getSebaranAnggaranDisetujui']);

Route::middleware('auth:sanctum')->group(function () {
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::get('/user', [AuthController::class, 'user']);
    Route::put('/user/profile', [AuthController::class, 'updateProfile']);
    Route::put('/user/change-password', [AuthController::class, 'changePassword']);

   Route::prefix('sub-jenis-bantuan')->group(function () {

    Route::get('/',
        [SubJenisBantuanController::class, 'index']
    );

    // by ID sub jenis bantuan
    Route::get('/detail/{id_subjenisbantuan}',
        [SubJenisBantuanController::class, 'show']
    );

    // by jenis bantuan
    Route::get('/jenis/{id_jenisbantuan}',
        [SubJenisBantuanController::class, 'getByJenisBantuan']
    );

    // by kategori
    Route::get('/kategori/{id_kategori}',
        [SubJenisBantuanController::class, 'getByKategori']
    );

});
   Route::prefix('sub-jenis-bantuan')->group(function () {
        Route::get('/',
            [SubJenisBantuanController::class, 'index']
        );

        // by ID sub jenis bantuan
        Route::get('/detail/{id_subjenisbantuan}',
            [SubJenisBantuanController::class, 'show']
        );

        // by jenis bantuan
        Route::get('/jenis/{id_jenisbantuan}',
            [SubJenisBantuanController::class, 'getByJenisBantuan']
        );

        // by kategori
        Route::get('/kategori/{id_kategori}',
            [SubJenisBantuanController::class, 'getByKategori']
        );

    });

    Route::prefix('kategori')->group(function () {

        Route::get('/',
            [KategoriController::class, 'index']
        );

        // by ID sub jenis bantuan
        Route::get('/detail/{id_kategori}',
            [KategoriController::class, 'show']
        );

        // by jenis bantuan
        Route::get('/jenis/{id_jenisbantuan}',
            [KategoriController::class, 'getByJenisBantuan']
        );

    });

    Route::prefix('jenis-bantuan')->group(function () {

        Route::get('/',
            [JenisBantuanController::class, 'index']
        );

        // by ID sub jenis bantuan
        Route::get('/detail/{id_jenisbantuan}',
            [JenisBantuanController::class, 'show']
        );

    });


    Route::apiResource('token', TokenController::class);

    Route::apiResource('spj', SpjController::class);

    Route::middleware('role:admin')->group(function () {
        Route::apiResource('users', UserController::class);
        Route::get('/users-search', [UserController::class, 'search']);
        Route::get('/users-by-role/{role}', [UserController::class, 'getByRole']);
        Route::get('/users-paginated', [UserController::class, 'paginated']);
        Route::put('/users/{id}/status', [UserController::class, 'updateStatus']);
    });

    Route::prefix('desa')->group(function () {
        Route::post('/', [DesaController::class, 'store']);
        Route::put('/{id}', [DesaController::class, 'update']);
        Route::delete('/{id}', [DesaController::class, 'destroy']);
        Route::patch('/{id}/coordinates', [DesaController::class, 'updateKoordinat']);
    });

    Route::prefix('kecamatan')->group(function () {
        Route::post('/', [KecamatanController::class, 'store']);
        Route::put('/{id}', [KecamatanController::class, 'update']);
        Route::delete('/{id}', [KecamatanController::class, 'destroy']);
    });

    Route::prefix('usulan')->group(function () {
        Route::get('/', [UsulanController::class, 'index']);
        Route::get('/{id}', [UsulanController::class, 'show']);
        Route::put('/{id}', [UsulanController::class, 'update']);
        Route::put('/{id}/status', [UsulanController::class, 'updateStatus']);
        Route::delete('/{id}', action: [UsulanController::class, 'destroy']);
        Route::post('/{id}/approve', [UsulanController::class, 'approve']);
        Route::get('/logs/all', [UsulanController::class, 'getLogs']);
        Route::get('/getByOpd/{kode_opd}', [UsulanController::class, 'getByOpd']);
    });

    // File Persyaratan Routes
    Route::post(
        'file-persyaratan',
        [FilePersyaratanController::class, 'store']
    );
    Route::get(
        'file-persyaratan/by-login-opd',
        [FilePersyaratanController::class, 'getByLoginOpd']
    );

    Route::put(
            'file-persyaratan/{id}',
            [FilePersyaratanController::class, 'update']
        );
    Route::delete(
            'file-persyaratan/{id}',
            [FilePersyaratanController::class, 'destroy']
        );
    // Usulan Persyaratan Routes


});

// usulan persyaratan routes (public)
Route::get(
    'usulan-persyaratan/{id}/download',
    [UsulanPersyaratanController::class, 'download']
);
Route::post(
        'usulan-persyaratan',
        [UsulanPersyaratanController::class, 'store']
    );

Route::put(
'usulan-persyaratan/{id}',
[UsulanPersyaratanController::class, 'update']
);

Route::delete(
'usulan-persyaratan/{id}',
[UsulanPersyaratanController::class, 'destroy']
);
// end usulan persyaratan routes

// buat usulan baru (public)
Route::post('usulan', [UsulanController::class, 'store']);

// Public File Persyaratan Routes
 Route::get(
        'file-persyaratan',
        [FilePersyaratanController::class, 'index']
    );
Route::get(
    'file-persyaratan/by-opd',
    [FilePersyaratanController::class, 'getByOpd']
);
 Route::get(
        'file-persyaratan/{id}',
        [FilePersyaratanController::class, 'show']
    );

