<?php

use App\Http\Controllers\API\AuthController;
use App\Http\Controllers\API\BoardCastController;
use App\Http\Controllers\API\CalonPPDBController;
use App\Http\Controllers\API\DaerahController;
use App\Http\Controllers\API\DataPesertaController;
use App\Http\Controllers\API\DesaController;
use App\Http\Controllers\API\KalenderPesertaController;
use App\Http\Controllers\API\KelasPesertaController;
use App\Http\Controllers\API\KelompokController;
use App\Http\Controllers\API\LogsController;
use App\Http\Controllers\API\PekerjaanPesertaController;
use App\Http\Controllers\API\PengajarPesertaController;
use App\Http\Controllers\API\PesertaDidikController;
use App\Http\Controllers\API\ProfileController;
use App\Http\Controllers\API\RerataNilaiController;
use App\Http\Controllers\API\RolesController;
use App\Http\Controllers\API\UserController;
use App\Http\Controllers\API\WalletKasController;
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
Route::post('/register_akun', [AuthController::class, 'register']);
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
        Route::get('list', [BoardCastController::class, 'list'])->middleware('role:1,2,3');
        Route::post('create', [BoardCastController::class, 'create'])->middleware('role:1');
        Route::post('edit', [BoardCastController::class, 'edit'])->middleware('role:1,2,3');
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

    // Kas Management
    Route::group(['prefix' => '/wallet_kas/'], function () {
        Route::get('getDataTahun', [WalletKasController::class, 'getDataTahun'])->middleware('role:1,3');
        Route::get('getDataTotal', [WalletKasController::class, 'getDataTotal'])->middleware('role:1,3');
        Route::get('totalSaldoPemasukan', [WalletKasController::class, 'totalSaldoPemasukan'])->middleware('role:1,3');
        Route::get('list', [WalletKasController::class, 'list'])->middleware('role:1,3');
        Route::post('create', [WalletKasController::class, 'create'])->middleware('role:1,3');
        Route::post('edit', [WalletKasController::class, 'edit'])->middleware('role:1,3');
        Route::post('update', [WalletKasController::class, 'update'])->middleware('role:1,3');
        Route::delete('delete', [WalletKasController::class, 'delete'])->middleware('role:1,3');
    });

    // Pekerjaan Management
    Route::group(['prefix' => '/pekerjaan/'], function () {
        Route::get('list', [PekerjaanPesertaController::class, 'list'])->middleware('role:1,4');
        Route::post('create', [PekerjaanPesertaController::class, 'create'])->middleware('role:1,4');
        Route::post('edit', [PekerjaanPesertaController::class, 'edit'])->middleware('role:1,4');
        Route::post('update', [PekerjaanPesertaController::class, 'update'])->middleware('role:1,4');
        Route::delete('delete', [PekerjaanPesertaController::class, 'delete'])->middleware('role:1,4');
    });

    // Pekerjaan Management
    Route::group(['prefix' => '/kelas_peserta/'], function () {
        Route::get('list', [KelasPesertaController::class, 'list'])->middleware('role:1,4');
        Route::post('create', [KelasPesertaController::class, 'create'])->middleware('role:1,4');
        Route::post('edit', [KelasPesertaController::class, 'edit'])->middleware('role:1,4');
        Route::post('update', [KelasPesertaController::class, 'update'])->middleware('role:1,4');
        Route::delete('delete', [KelasPesertaController::class, 'delete'])->middleware('role:1,4');
    });

    // Pekerjaan Management
    Route::group(['prefix' => '/kalender_pendidikan/'], function () {
        Route::get('list', [KalenderPesertaController::class, 'list'])->middleware('role:1,4');
        Route::post('create', [KalenderPesertaController::class, 'create'])->middleware('role:1,4');
        Route::post('edit', [KalenderPesertaController::class, 'edit'])->middleware('role:1,4');
        Route::post('update', [KalenderPesertaController::class, 'update'])->middleware('role:1,4');
        Route::delete('delete', [KalenderPesertaController::class, 'delete'])->middleware('role:1,4');
    });

    // Pengajar Management
    Route::group(['prefix' => '/pengajar/'], function () {
        Route::get('data_pengajar', [PengajarPesertaController::class, 'data_pengajar'])->middleware('role:1,4');
        Route::get('list', [PengajarPesertaController::class, 'list'])->middleware('role:1,4');
        Route::post('create', [PengajarPesertaController::class, 'create'])->middleware('role:1,4');
        Route::post('edit', [PengajarPesertaController::class, 'edit'])->middleware('role:1,4');
        Route::post('update', [PengajarPesertaController::class, 'update'])->middleware('role:1,4');
        Route::delete('delete', [PengajarPesertaController::class, 'delete'])->middleware('role:1,4');
    });

    // Peserta Didik Management
    Route::group(['prefix' => '/peserta_didik/'], function () {
        Route::get('list', [PesertaDidikController::class, 'list'])->middleware('role:1,4');
        Route::post('create', [PesertaDidikController::class, 'create'])->middleware('role:1,4');
        Route::post('edit', [PesertaDidikController::class, 'edit'])->middleware('role:1,4');
        Route::post('update', [PesertaDidikController::class, 'update'])->middleware('role:1,4');
        Route::delete('delete', [PesertaDidikController::class, 'delete'])->middleware('role:1,4');
    });

    // Rerata Nilai Peserta Didik Management
    Route::group(['prefix' => '/rerata_nilai/'], function () {
        Route::get('list', [RerataNilaiController::class, 'list'])->middleware('role:1,4');
        Route::post('create', [RerataNilaiController::class, 'create'])->middleware('role:1,4');
        Route::post('edit', [RerataNilaiController::class, 'edit'])->middleware('role:1,4');
        Route::post('update', [RerataNilaiController::class, 'update'])->middleware('role:1,4');
        Route::delete('delete', [RerataNilaiController::class, 'delete'])->middleware('role:1,4');
    });

    // Calon Pendaftaran Peserta Didik Management
    Route::group(['prefix' => '/calon_ppdb/'], function () {
        Route::get('list', [CalonPPDBController::class, 'list'])->middleware('role:1,4');
        Route::post('create', [CalonPPDBController::class, 'create'])->middleware('role:1,4');
        Route::post('edit', [CalonPPDBController::class, 'edit'])->middleware('role:1,4');
        Route::post('update', [CalonPPDBController::class, 'update'])->middleware('role:1,4');
        Route::delete('delete', [CalonPPDBController::class, 'delete'])->middleware('role:1,4');
    });
});
