<?php

use App\Http\Controllers\BukuController;
use App\Http\Controllers\KategoriController;
use App\Http\Controllers\PeminjamanController;
use App\Http\Controllers\Soap\LibrarySoapController;
use Illuminate\Support\Facades\Route;

Route::get('/', fn() => redirect()->route('buku.index'));
Route::resource('kategori', KategoriController::class);
Route::resource('buku', BukuController::class);

// Sirkulasi Peminjaman & Pengembalian Buku (ACID & lockForUpdate)
Route::get('peminjaman', [PeminjamanController::class, 'index'])->name('peminjaman.index');
Route::get('peminjaman/create', [PeminjamanController::class, 'create'])->name('peminjaman.create');
Route::post('peminjaman', [PeminjamanController::class, 'store'])->name('peminjaman.store');
Route::post('peminjaman/{id}/kembalikan', [PeminjamanController::class, 'kembalikan'])->name('peminjaman.kembalikan');

// Endpoint Layanan Legacy SOAP (Sistem Bebas Pustaka Yudisium)
Route::get('/soap/sipus.wsdl', [LibrarySoapController::class, 'wsdl']);
Route::post('/soap/sipus', [LibrarySoapController::class, 'handle']);

