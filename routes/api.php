<?php

use App\Http\Controllers\API\AuthController;
use App\Http\Controllers\API\BoardCastController;
use App\Http\Controllers\API\DaerahController;
use App\Http\Controllers\API\DataPesertaController;
use App\Http\Controllers\API\DesaController;
use App\Http\Controllers\API\KelompokController;
use App\Http\Controllers\API\LogsController;
use App\Http\Controllers\API\ProfileController;
use App\Http\Controllers\API\RolesController;
use App\Http\Controllers\API\UserController;
use App\Http\Controllers\DataSensusController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group. Make something great!
|
*/

Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});

Route::post('/login', [AuthController::class, 'login']);
Route::post('/find_sensus', [DataSensusController::class, 'cari_data']);

Route::middleware(['auth:sanctum'])->group(function () {
    // User Management
    Route::group(['prefix' => '/user/'], function () {
        Route::get('list', [UserController::class, 'list'])->middleware('role:1');
        Route::post('create', [UserController::class, 'create'])->middleware('role:1');
        Route::post('edit', [UserController::class, 'edit'])->middleware('role:1');
        Route::post('update', [UserController::class, 'update'])->middleware('role:1');
        Route::delete('delete', [UserController::class, 'delete'])->middleware('role:1');
        Route::post('logout', [AuthController::class, 'logout']);
    });

    // Logs
    Route::group(['prefix' => '/logs/'], function () {
        Route::get('list_browser', [LogsController::class, 'list_browser'])->middleware('role:1');
        Route::get('list_os', [LogsController::class, 'list_os'])->middleware('role:1');
        Route::get('list_device', [LogsController::class, 'list_device'])->middleware('role:1');
        Route::get('list_status', [LogsController::class, 'list_status'])->middleware('role:1');
        Route::get('list', [LogsController::class, 'list'])->middleware('role:1');
    });

    // Role Management
    Route::group(['prefix' => '/role/'], function () {
        Route::get('list', [RolesController::class, 'list'])->middleware('role:1');
        Route::post('create', [RolesController::class, 'create'])->middleware('role:1');
        Route::post('edit', [RolesController::class, 'edit'])->middleware('role:1');
        Route::post('update', [RolesController::class, 'update'])->middleware('role:1');
        Route::delete('delete', [RolesController::class, 'delete'])->middleware('role:1');
    });

    // Daerah Management
    Route::group(['prefix' => '/daerah/'], function () {
        Route::get('list', [DaerahController::class, 'list'])->middleware('role:1,2');
        Route::post('create', [DaerahController::class, 'create'])->middleware('role:1');
        Route::post('edit', [DaerahController::class, 'edit'])->middleware('role:1');
        Route::post('update', [DaerahController::class, 'update'])->middleware('role:1');
        Route::delete('delete', [DaerahController::class, 'delete'])->middleware('role:1');
    });

    // Desa Management
    Route::group(['prefix' => '/desa/'], function () {
        Route::get('list', [DesaController::class, 'list'])->middleware('role:1,2');
        Route::post('create', [DesaController::class, 'create'])->middleware('role:1');
        Route::post('edit', [DesaController::class, 'edit'])->middleware('role:1');
        Route::post('update', [DesaController::class, 'update'])->middleware('role:1');
        Route::delete('delete', [DesaController::class, 'delete'])->middleware('role:1');
    });

    // Kelompok Management
    Route::group(['prefix' => '/kelompok/'], function () {
        Route::get('list', [KelompokController::class, 'list'])->middleware('role:1,2');
        Route::post('create', [KelompokController::class, 'create'])->middleware('role:1');
        Route::post('edit', [KelompokController::class, 'edit'])->middleware('role:1');
        Route::post('update', [KelompokController::class, 'update'])->middleware('role:1');
        Route::delete('delete', [KelompokController::class, 'delete'])->middleware('role:1');
    });

    // Sensus Management
    Route::group(['prefix' => '/sensus/'], function () {
        Route::get('dashboard_sensus', [DataPesertaController::class, 'dashboard_sensus'])->middleware('role:1,2');
        Route::get('list_daerah', [DataPesertaController::class, 'list_daerah'])->middleware('role:1,2');
        Route::get('list_desa', [DataPesertaController::class, 'list_desa'])->middleware('role:1,2');
        Route::get('list_kelompok', [DataPesertaController::class, 'list_kelompok'])->middleware('role:1,2');
        Route::get('list', [DataPesertaController::class, 'list']);
        Route::get('listByPtgs', [DataPesertaController::class, 'listByPtgs'])->middleware('role:1,2');
        Route::post('create', [DataPesertaController::class, 'create'])->middleware('role:1,2');
        Route::post('edit', [DataPesertaController::class, 'edit'])->middleware('role:1,2');
        Route::post('update', [DataPesertaController::class, 'update'])->middleware('role:1,2');
        Route::delete('delete', [DataPesertaController::class, 'delete'])->middleware('role:1,2');
    });

    // Pengumuman
    Route::group(['prefix' => '/boardcast/'], function () {
        Route::get('list', [BoardCastController::class, 'list'])->middleware('role:1,2');
        Route::post('create', [BoardCastController::class, 'create'])->middleware('role:1');
        Route::post('edit', [BoardCastController::class, 'edit'])->middleware('role:1,2');
        Route::post('update', [BoardCastController::class, 'update'])->middleware('role:1');
        Route::delete('delete', [BoardCastController::class, 'delete'])->middleware('role:1');
    });

    // Profile Management
    Route::group(['prefix' => '/profile/'], function () {
        Route::post('edit', [ProfileController::class, 'edit']);
        Route::post('update', [ProfileController::class, 'update']);
        Route::post('cek_password', [ProfileController::class, 'cek_password']);
        Route::post('update_password', [ProfileController::class, 'update_password']);
    });
});
