# 📚 SIPUS-Del — Sistem Informasi Perpustakaan Kampus Del (Tahap 2)

> **Modul Praktikum Minggu 06 — Pemrograman & Pengujian Web (12S3101)**  
> **Fakultas Informatika dan Teknik Elektro — Institut Teknologi Del**  
> **Dosen Pengampu:** Chandro Pardede, S.Kom., M.Sc.  
> **Branch Tugas:** `week6-db-session`  
> **Mahasiswa:** Josua Sianturi (12S24035)

---

## 🔐 Kredensial Akun Pustakawan Uji Coba

Gunakan kredensial resmi berikut untuk menguji sistem login, sesi, proteksi middleware `auth`, dan sirkulasi transaksi perpustakaan:

| Parameter | Nilai Kredensial |
| :--- | :--- |
| **Email Pustakawan** | `pustakawan@del.ac.id` |
| **Kata Sandi (Password)** | `password123` |
| **Peran (Role)** | Pustakawan Utama Perpustakaan IT Del |
| **Masa Aktif Cookie Email** | 10.080 menit (7 hari) |

> [!NOTE]  
> Seluruh password dienkripsi dengan algoritma aman Bcrypt (`Hash::make('password123')`).

---

## 📖 Deskripsi Proyek (Tahap 2)

**SIPUS-Del Tahap 2** adalah pengembangan lanjutan dari Sistem Informasi Perpustakaan Kampus Del dengan fokus pada:
1. **Konektivitas Basis Data Persisten (RDBMS MySQL)** melalui socket TCP/IP (Port 3306) dan arsitektur abstraksi PDO.
2. **Transaksi Basis Data ACID & Concurrency Control** membungkus operasi peminjaman dan pengembalian dalam `DB::transaction()` serta mengaplikasikan *Pessimistic Locking* (`lockForUpdate()`) guna mencegah *Race Condition* dan stok buku bernilai minus.
3. **Fluent Query Builder (`DB::table`) & Optimasi Eloquent** untuk rekapitulasi analitik dasbor cepat, optimasi kueri relasi bebas N+1 problem dengan `withCount('peminjamans')`, serta filter pencarian dinamis berbasis `when()` dan paginasi `withQueryString()`.
4. **Manajemen Sesi Server-Side & Keamanan HTTP Cookie** dengan regenerasi Session ID (`session()->regenerate()`) anti-*Session Fixation*, proteksi CSRF, pembersihan sesi aman saat logout, serta pelacakan state menggunakan `Cookie::queue()`.

---

## 🗄️ Skema Database Relasional (Entity Relationship Diagram - ERD)

Aplikasi memiliki relasi berjenjang antar-entitas:
- **`kategoris` (1) ───< (N) `bukus`**: Satu kategori mengelompokkan banyak buku (`cascadeOnDelete`).
- **`bukus` (1) ───< (N) `peminjamans`**: Satu judul buku dapat dipinjam dalam banyak catatan sirkulasi peminjaman (`cascadeOnDelete`).

```
┌──────────────────────────────┐          ┌───────────────────────────────────┐          ┌─────────────────────────────────────────┐
│          kategoris           │          │               bukus               │          │               peminjamans               │
├──────────────────────────────┤          ├───────────────────────────────────┤          ├─────────────────────────────────────────┤
│ id (PK, BIGINT)              │ 1      N │ id (PK, BIGINT)                   │ 1      N │ id (PK, BIGINT)                         │
│ kode_kategori (VARCHAR, UNIQ)│─────────<│ isbn (VARCHAR, UNIQ)              │─────────<│ kode_pinjam (VARCHAR, UNIQ)             │
│ nama_kategori (VARCHAR)      │          │ judul (VARCHAR)                   │          │ buku_id (FK -> bukus.id)                │
│ created_at / updated_at      │          │ penulis (VARCHAR)                 │          │ nim_peminjam (VARCHAR(15))              │
└──────────────────────────────┘          │ penerbit (VARCHAR)                │          │ nama_peminjam (VARCHAR(150))            │
                                          │ tahun_terbit (INTEGER)            │          │ tanggal_pinjam (DATE)                   │
                                          │ kategori_id (FK -> kategoris.id)  │          │ tanggal_jatuh_tempo (DATE)              │
                                          │ stok (INTEGER, DEFAULT 0)         │          │ tanggal_kembali (DATE, NULLABLE)        │
                                          │ sinopsis (TEXT, NULLABLE)         │          │ status ('dipinjam','dikembalikan')      │
                                          │ created_at / updated_at           │          │ created_at / updated_at                 │
                                          └───────────────────────────────────┘          └─────────────────────────────────────────┘
```

---

## ⚡ Implementasi Transaksi ACID & Pessimistic Locking (`lockForUpdate`)

### 1. Mengapa `lockForUpdate()` Krusial? (Pencegahan Race Condition)
Apabila sisa stok buku tinggal **1 eksemplar**, dan terdapat **dua pengguna** yang menekan tombol pinjam pada milidetik yang sama tanpa mekanisme penguncian:
1. Kedua request membaca stok yang sama (`stok = 1 > 0`).
2. Kedua proses lolos validasi stok.
3. Keduanya menjalankan decrement stok, sehingga sisa stok menjadi **-1 (minus)** dan integritas data rusak (*over-borrowing*).

### 2. Mekanisme Solusi pada `PeminjamanController`
Dengan menyertakan `DB::transaction()` dan `lockForUpdate()`:
```php
DB::transaction(function () use ($validated, $nim, $nama) {
    // 1. Kunci baris buku secara eksklusif (SELECT ... FOR UPDATE)
    $buku = Buku::where('id', $validated['buku_id'])->lockForUpdate()->firstOrFail();

    // 2. Pemeriksaan stok ketat (Rollback jika stok = 0)
    if ($buku->stok < 1) {
        throw new \RuntimeException("Transaksi Ditolak: Stok buku '{$buku->judul}' telah habis!");
    }

    // 3. Pencegahan peminjaman ganda oleh mahasiswa yang sama
    $masihDipinjam = Peminjaman::where('buku_id', $buku->id)
        ->where('nim_peminjam', $nim)
        ->where('status', 'dipinjam')
        ->lockForUpdate()
        ->exists();

    if ($masihDipinjam) {
        throw new \RuntimeException("Transaksi Ditolak: Mahasiswa NIM {$nim} belum mengembalikan buku ini.");
    }

    // 4. Mutasi stok atomik
    $buku->decrement('stok');

    // 5. Catat riwayat ke tabel sirkulasi peminjaman
    return Peminjaman::create([...]);
}, 3); // 3x percobaan retry jika terjadi deadlock
```
> Kueri transaksi kedua akan **mengantre (blocking wait)** pada level database engine (InnoDB) hingga transaksi pertama tuntas (`COMMIT` atau `ROLLBACK`).

---

## 📊 Dasbor Analitik Query Builder (`DB::table`) & Filter Dinamis

1. **Rekapitulasi Agregasi Cepat (`DB::table`)**:
   - `total_judul`: `DB::table('bukus')->count()`
   - `total_stok`: `DB::table('bukus')->sum('stok')`
   - `total_dipinjam`: `DB::table('peminjamans')->where('status', 'dipinjam')->count()`
   - `total_dikembalikan`: `DB::table('peminjamans')->where('status', 'dikembalikan')->count()`
2. **Eliminasi N+1 Problem dengan `withCount()`**:
   Katalog buku menampilkan jumlah frekuensi peminjaman tiap buku menggunakan `withCount('peminjamans')` dan eager loading kategori `with('kategori')`.
3. **Filter Dinamis & Paginasi Bersih**:
   Pencarian dinamis multikolom (Judul, Penulis, ISBN) dan filter Kategori diproses via klausul `when()`, dengan paginasi mempertahankan parameter URL melalui `withQueryString()`.

---

## 🛡️ Manajemen Sesi & Keamanan Cookie

| Fitur Keamanan | Implementasi Teknis | Manfaat Mitigasi |
| :--- | :--- | :--- |
| **Session Fixation Prevention** | `$request->session()->regenerate()` saat login berhasil | Mencegah penyerang membajak session ID lama pengguna sebelum login. |
| **Session Invalidation** | `$request->session()->invalidate()` & `regenerateToken()` saat logout | Memusnahkan payload sesi di server dan meremajakan CSRF token. |
| **HttpOnly Flag** | Header `Set-Cookie` sesi Laravel otomatis berflag `HttpOnly` | Skrip JavaScript berbahaya (XSS) tidak dapat membaca atau mencuri cookie sesi. |
| **SameSite Policy** | `SameSite=Lax` pada konfigurasi cookie sesi | Mencegah request CSRF lintas domain tak terotorisasi. |
| **HTTP Cookie State** | `Cookie::queue('del_last_user_email', ...)` & `last_peminjam_nim` | Mengingat email pustakawan dan NIM peminjam terakhir untuk efisiensi input. |

---

## 💻 Panduan Menjalankan Proyek

### Prasyarat Lingkungan:
- **PHP** >= 8.2 CLI (ekstensi `pdo_mysql`, `pdo_sqlite`, `mbstring`, `openssl` aktif)
- **MySQL** 8.0 / MariaDB (via Laragon Port 3306)
- **Composer** >= 2.6.x

### Langkah Instalasi & Menjalankan:
```bash
# 1. Pastikan berada di branch praktikum minggu 6
git checkout week6-db-session

# 2. Salin environment & konfigurasi koneksi database
cp .env.example .env

# Pastikan isi konfigurasi database pada .env:
# DB_CONNECTION=mysql
# DB_HOST=127.0.0.1
# DB_PORT=3306
# DB_DATABASE=sipus_del
# DB_USERNAME=root
# DB_PASSWORD=

# 3. Jalankan migrasi tabel ke MySQL
php artisan migrate

# 4. Jalankan seeder akun pustakawan, sampel buku (stok 0 & 1), dan sampel transaksi
php artisan db:seed

# 5. Jalankan server lokal
php artisan serve
```

---

## 🧪 Bukti Hasil Pengujian Otomatis (PHPUnit)

Seluruh 17 pengujian otomatis berjalan dengan status **100% PASS**:

```bash
php vendor/phpunit/phpunit/phpunit
```

```text
PHPUnit 12.5.38 by Sebastian Bergmann and contributors.

Runtime:       PHP 8.3.35
Configuration: C:\laragon\www\P05_12S24035_JOSUA\phpunit.xml

.................                                                 17 / 17 (100%)

Time: 00:15.933, Memory: 56.00 MB

OK (17 tests, 61 assertions)
```

### Rincian Skenario Pengujian:
1. `test_login_screen_can_be_rendered` &rarr; Form login pustakawan dapat dibuka.
2. `test_librarian_can_authenticate_with_session_regeneration_and_cookies` &rarr; Login sukses, regenerasi sesi aktif, cookie email tersimpan.
3. `test_librarian_cannot_authenticate_with_invalid_password` &rarr; Login gagal jika password salah.
4. `test_librarian_can_logout_and_session_is_invalidated` &rarr; Sesi musnah saat logout.
5. `test_unauthenticated_user_redirected_to_login_on_protected_routes` &rarr; Guest otomatis diarahkan ke login saat mengakses rute mutasi.
6. `test_peminjaman_berhasil_mengurangi_stok_secara_atomik` &rarr; Peminjaman memotong stok secara atomik.
7. `test_penolakan_transaksi_peminjaman_saat_stok_habis` &rarr; Rollback dan penolakan saat stok = 0 (stok tidak minus).
8. `test_penolakan_peminjaman_ganda_oleh_nim_yang_sama` &rarr; Mahasiswa tidak dapat meminjam buku yang sama sebelum dikembalikan.
9. `test_pengembalian_buku_berhasil_menambah_stok_kembali` &rarr; Pengembalian menambah kembali stok buku.
10. `test_katalog_buku_displays_query_builder_statistics_and_withcount_peminjamans` &rarr; Dasbor statistik Query Builder & withCount bebas N+1 problem.

---

## 📸 Lampiran Bukti Audit DevTools & Pengujian Transaksi

> **Catatan Mahasiswa:** Tangkap layar (screenshot) hasil pengujian langsung di peramban (Chrome/Edge DevTools):

### 1. Bukti Login & Inspeksi Cookie `HttpOnly` (Browser DevTools)
*Buka DevTools (F12) &rarr; Tab **Application** &rarr; Menu **Cookies** &rarr; `http://localhost:8000`:*
- Periksa kolom `HttpOnly` tercentang aktif pada cookie `laravel_session`.
- Periksa cookie antrean `del_last_user_email` dan `last_peminjam_nim`.

*(Lampirkan file screenshot di: `public/screenshots/cookie-httponly-audit.png`)*

### 2. Bukti Transaksi Peminjaman & Pengurangan Stok Atomik
- Tangkapan layar formulir peminjaman buku berhasil disimpan dan stok buku berkurang 1.

*(Lampirkan file screenshot di: `public/screenshots/transaksi-pinjam-berhasil.png`)*

### 3. Bukti Penolakan Transaksi Saat Stok Habis (Stok = 0)
- Tangkapan layar pesan error penolakan ketika mencoba meminjam buku dengan stok = 0.

*(Lampirkan file screenshot di: `public/screenshots/penolakan-stok-habis.png`)*

### 4. Bukti Transaksi Pengembalian Buku & Penambahan Stok Kembali
- Tangkapan layar tombol pengembalian buku dan status berubah menjadi *Dikembalikan* serta stok bertambah 1.

*(Lampirkan file screenshot di: `public/screenshots/transaksi-kembali-berhasil.png`)*

---

## 📜 Riwayat Git Commit (Conventional Commits)

```
* feat(auth-session): implement librarian login, session regeneration, and cookies
* feat(query-builder): add library analytics dashboard and dynamic search
* feat(transaction): implement ACID book borrowing and return with lockForUpdate
* feat(migration): create peminjamans table with foreign key to bukus
* chore: update layout component
* docs: add comprehensive documentation and sql audit evidence
```

---
*Dibuat untuk memenuhi Tugas Praktikum Minggu 06 Mata Kuliah Pemrograman & Pengujian Web (12S3101) — Institut Teknologi Del.*
