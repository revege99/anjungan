<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\ReportUiController;
use App\Http\Controllers\UserManagementController;
use Illuminate\Support\Facades\Route;

Route::middleware('guest')->group(function () {
    Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
    Route::post('/login', [AuthController::class, 'login'])->name('login.attempt');
});

Route::middleware('auth')->group(function () {
    Route::post('/logout', [AuthController::class, 'logout'])->name('logout');

    Route::get('/', [ReportUiController::class, 'dashboard'])->name('dashboard');
    Route::get('/transaksi-pasien', [ReportUiController::class, 'transaksiPasien'])->name('transaksi-pasien');
    Route::post('/transaksi-pasien/sync-rekap', [ReportUiController::class, 'syncRekapPasien'])->name('transaksi-pasien.sync-rekap');
    Route::post('/transaksi-pasien', [ReportUiController::class, 'storeTransaksiPasien'])->name('transaksi-pasien.store');
    Route::delete('/transaksi-pasien/{transaksiPasien}', [ReportUiController::class, 'destroyTransaksiPasien'])->name('transaksi-pasien.destroy');
    Route::get('/input-pengeluaran', [ReportUiController::class, 'inputPengeluaran'])->name('input-pengeluaran');
    Route::post('/input-pengeluaran', [ReportUiController::class, 'storePengeluaran'])->name('input-pengeluaran.store');
    Route::delete('/input-pengeluaran/{pengeluaran}', [ReportUiController::class, 'destroyPengeluaran'])->name('input-pengeluaran.destroy');

    Route::middleware('admin_or_master')->group(function () {
        Route::put('/transaksi-pasien/{transaksiPasien}', [ReportUiController::class, 'updateTransaksiPasien'])->name('transaksi-pasien.update');
        Route::get('/profile-klinik', [ReportUiController::class, 'profileKlinik'])->name('profile-klinik');
        Route::post('/profile-klinik', [ReportUiController::class, 'saveProfileKlinik'])->name('profile-klinik.save');
        Route::put('/input-pengeluaran/{pengeluaran}', [ReportUiController::class, 'updatePengeluaran'])->name('input-pengeluaran.update');
        Route::get('/rekap-bulanan', [ReportUiController::class, 'rekapBulanan'])->name('rekap-bulanan');
        Route::get('/rekap-tahunan', [ReportUiController::class, 'rekapTahunan'])->name('rekap-tahunan');
    });

    Route::middleware('master')->group(function () {
        Route::get('/kode-layanan', [ReportUiController::class, 'kodeLayanan'])->name('kode-layanan');
        Route::post('/kode-layanan', [ReportUiController::class, 'storeKodeLayanan'])->name('kode-layanan.store');
        Route::put('/kode-layanan/{masterLayanan}', [ReportUiController::class, 'updateKodeLayanan'])->name('kode-layanan.update');
        Route::get('/kode-pengeluaran', [ReportUiController::class, 'kodePengeluaran'])->name('kode-pengeluaran');
        Route::post('/kode-pengeluaran', [ReportUiController::class, 'storeKodePengeluaran'])->name('kode-pengeluaran.store');
        Route::put('/kode-pengeluaran/{masterKategoriPengeluaran}', [ReportUiController::class, 'updateKodePengeluaran'])->name('kode-pengeluaran.update');
        Route::get('/manajemen-user', [UserManagementController::class, 'index'])->name('manajemen-user');
        Route::post('/manajemen-user', [UserManagementController::class, 'store'])->name('manajemen-user.store');
        Route::put('/manajemen-user/{user}', [UserManagementController::class, 'update'])->name('manajemen-user.update');
    });
});
