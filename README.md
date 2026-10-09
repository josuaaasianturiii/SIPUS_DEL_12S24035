# 📚 SIPUS-Del — Sistem Informasi Perpustakaan Kampus Del

> **Modul Praktikum Minggu 05 — Pemrograman & Pengujian Web (12S3101)**  
> **Fakultas Informatika dan Teknik Elektro — Institut Teknologi Del**  
> **Dosen Pengampu:** Chandro Pardede, S.Kom., M.Sc.

---

## 📖 Deskripsi Proyek
**SIPUS-Del (Sistem Informasi Perpustakaan Kampus Del)** adalah aplikasi web berbasis *Server-Side Rendering* (SSR) yang dikembangkan dengan framework **Laravel 11.x**. Proyek ini mengimplementasikan pola arsitektur **Model-View-Controller (MVC)**, relasi data multi-entitas dengan **Eloquent ORM**, validasi input form yang ketat di sisi server (*Server-Side Validation*), proteksi keamanan web (**CSRF & XSS Prevention**), arsitektur tampilan modular menggunakan **Blade Component Engine**, serta mitigasi **N+1 Query Problem** melalui **Eager Loading**.

---

## 🏛️ Arsitektur MVC & Siklus Hidup Request (Request Lifecycle)

```
[ HTTP Request ]
       │
       ▼
 [ public/index.php ] ────────▶ Titik masuk tunggal aplikasi & memuat Composer Autoloader
       │
       ▼
[ bootstrap/app.php ] ────────▶ Inisialisasi Service Container (IoC) & Pipeline Middleware
       │
       ▼
[ HTTP Middleware ]  ────────▶ Proteksi CSRF (@csrf), session state, dan enkripsi cookie
       │
       ▼
 [ routes/web.php ]  ────────▶ Resolusi rute RESTful resource & Implicit Route Model Binding
       │
       ▼
 [ Controller ]      ────────▶ Orkestrasi logika bisnis & validasi ketat $request->validate()
    ┌──┴──┐
    ▼     ▼
[ Model ] [ View ]   ────────▶ Interaksi database via Eloquent & render antarmuka <x-layout>
    │     │
    └─────┼──────────────────▶ [ HTTP Response ] (Payload HTML 200 OK ke Browser)
```

---

## 🗄️ Skema Database Relasional (Database Schema)

Aplikasi memiliki relasi **One-to-Many** antara entitas `Kategori` dan `Buku` (*1 Kategori memiliki banyak Buku, 1 Buku dimiliki oleh 1 Kategori*):

```
┌──────────────────────────────┐          ┌───────────────────────────────────┐
│          kategoris           │          │               bukus               │
├──────────────────────────────┤          ├───────────────────────────────────┤
│ id (PK)                      │ 1      N │ id (PK)                           │
│ kode_kategori (VARCHAR, UNIQ)│─────────<│ isbn (VARCHAR, UNIQ)              │
│ nama_kategori (VARCHAR)      │          │ judul (VARCHAR)                   │
│ created_at / updated_at      │          │ penulis (VARCHAR)                 │
└──────────────────────────────┘          │ penerbit (VARCHAR)                │
                                          │ tahun_terbit (INTEGER)            │
                                          │ kategori_id (FK -> kategoris.id)  │
                                          │ stok (INTEGER, DEFAULT 0)         │
                                          │ sinopsis (TEXT, NULLABLE)         │
                                          │ created_at / updated_at           │
                                          └───────────────────────────────────┘
```

---

## 🚀 Fitur & Spesifikasi Teknis

1. **Routing RESTful & Resource Controller**:
   - `Route::resource('kategori', KategoriController::class)`: 7 aksi baku CRUD kategori.
   - `Route::resource('buku', BukuController::class)`: 7 aksi baku CRUD buku.
   - *Implicit Route Model Binding* pada rute dinamis seperti `/buku/{buku}` dan `/kategori/{kategori}`.
2. **Validasi Server-Side Ketat**:
   - Pengecekan keunikan ISBN dan Kode Kategori dengan pengecualian ID saat update (`Rule::unique()->ignore()`).
   - Validasi panjang minimal judul (min: 5 karakter), rentang tahun terbit realistis, dan stok non-negatif (min: 0).
3. **Keamanan Web (Security First)**:
   - Proteksi serangan *Cross-Site Request Forgery* (**CSRF**) menggunakan direktif `@csrf` pada seluruh form POST, PUT, dan DELETE.
   - *Method Spoofing* `@method('PUT')` dan `@method('DELETE')`.
   - Pencegahan *Cross-Site Scripting* (**XSS**) melalui auto-escaping Blade `{{ ... }}`.
4. **Desain Antarmuka Modular**:
   - Master Shell Layout `<x-layout>` bertema **Del Royal Purple (`#4C1D95`)**.
   - Penanganan notifikasi flash session (`session('success')` dan `session('error')`).
   - Pagination aktif dengan Bootstrap 5 (`Paginator::useBootstrapFive()`).
   - Direktif penanganan koleksi kosong secara elegan dengan `@forelse ... @empty ... @endforelse`.
5. **Mock Data Realistis (Factory & Seeder)**:
   - Seeder otomatis untuk **6 Kategori Master** dan **30+ Data Buku Realistis** memanfaatkan pustaka **Faker**.

---

## ⚡ Bukti Audit Kinerja Kueri SQL (Mitigasi N+1 Problem)

Untuk membuktikan efisiensi kueri database, telah dipasang event listener `DB::listen` pada `app/Providers/AppServiceProvider.php`.

### 1. Implementasi Eager Loading pada Controller
```php
// app/Http/Controllers/BukuController.php
public function index()
{
    // Eager Loading relasi kategori untuk mencegah N+1 Query Problem
    $bukus = Buku::with('kategori')->latest()->paginate(10);
    return view('buku.index', compact('bukus'));
}
```

### 2. Cuplikan Log Audit SQL dari `storage/logs/laravel.log`
```log
[SQL AUDIT] (2.77 ms) select count(*) as "aggregate" from "bukus"
[SQL AUDIT] (1.32 ms) select * from "bukus" order by "created_at" desc limit 10 offset 0
[SQL AUDIT] (0.38 ms) select * from "kategoris" where "kategoris"."id" in (1, 2)
```

> **Analisis Kinerja:**  
> - Tanpa Eager Loading (*Lazy Loading*), pemanggilan `$buku->kategori->nama_kategori` pada 10 baris buku akan mengeksekusi **11 kueri SQL** (1 kueri buku + 10 kueri kategori terpisah).  
> - Dengan Eager Loading (`Buku::with('kategori')`), kueri diringkas menjadi tepat **2 kueri SQL terpadu** (menggunakan klausa `WHERE id IN (1, 2)`), sehingga melipatgandakan performa aplikasi secara signifikan.

---

## 💻 Panduan Instalasi & Menjalankan Proyek

### Prasyarat:
- **PHP** >= 8.2 (dengan ekstensi `pdo_sqlite` / `pdo_mysql`, `mbstring`, `openssl`, `curl`)
- **Composer** >= 2.6.x
- **Node.js** >= 18.x & **NPM**

### Langkah Menjalankan:
```bash
# 1. Clone repositori
git clone <URL_REPOSITORI_ANDA>
cd kelompok-5-ppw

# 2. Salin berkas lingkungan .env
cp .env.example .env

# 3. Instal dependensi PHP via Composer
composer install

# 4. Generate Application Encryption Key
php artisan key:generate

# 5. Jalankan migrasi skema dan suntikkan mock data seeder
php artisan migrate:fresh --seed

# 6. Jalankan automated unit & feature testing
php artisan test

# 7. Jalankan web server lokal Laravel Artisan
php artisan serve
```
Akses aplikasi melalui peramban di: **`http://localhost:8000`**

---

## 🧪 Hasil Pengujian Otomatis (Automated Testing)

Semua skenario pengujian fitur (*Feature Tests*) dan pengujian unit (*Unit Tests*) telah berjalan dengan sukses (**100% PASS**):

```
   PASS  Tests\Unit\ExampleTest
  ✓ that true is true

   PASS  Tests\Feature\ExampleTest
  ✓ the application returns a successful response                                 0.39s  

   PASS  Tests\Feature\SipusDelTest
  ✓ root redirects to buku index                                                  0.22s  
  ✓ can view buku index with eager loading                                        0.07s  
  ✓ validation prevents duplicate isbn and short title                            0.05s  
  ✓ can create new buku and kategori                                              0.04s  

  Tests:    6 passed (14 assertions)
  Duration: 1.30s
```

---

## 📜 Riwayat Git Commit (Conventional Commits)

```
dae697d perf(eager-loading): optimize sql queries to prevent n+1 problem
7167687 feat(views): construct blade component layout and crud templates
997d4ff feat(controller): implement restful actions and server validation
b249348 feat(model): define relationships and fillable attributes
28aeb28 feat(migration): create kategoris and bukus tables schema
```

---
*Dibuat untuk memenuhi Tugas Praktikum Minggu 05 Mata Kuliah Pemrograman & Pengujian Web — Institut Teknologi Del.*
