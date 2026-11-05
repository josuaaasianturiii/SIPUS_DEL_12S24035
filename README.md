# 📚 SIPUS-Del — Sistem Informasi Perpustakaan Kampus Del
## Tahap 3: Distributed Web Services & RESTful Web API (API v1)

> **Modul Praktikum Minggu 09 — Pemrograman & Pengujian Aplikasi Web (12S3101)**  
> **Fakultas Informatika dan Teknik Elektro — Institut Teknologi Del**  
> **Dosen Pengampu:** Chandro Pardede, S.Kom., M.Sc.

---

## 👤 Identitas Mahasiswa
- **Nama Lengkap** : Josua Sianturi
- **NIM**          : 12S24035
- **Kelas**        : 12S24
- **Program Studi**: Sarjana Sistem Informasi
- **Repositori**   : `josuaaasianturiii/SIPUS_DEL_12S24035`
- **Branch Aktif** : `week9-rest-api`

---

## 📑 Daftar Isi
1. [Deskripsi & Evolusi Arsitektur](#-deskripsi--evolusi-arsitektur)
2. [Analisis Kebutuhan & Kelayakan Web Services](#-analisis-kebutuhan--kelayakan-web-services)
3. [Tabel Katalog Endpoint Web Services (SOAP & REST API v1)](#-tabel-katalog-endpoint-web-services)
4. [Karakteristik & Keunggulan Implementasi Teknis](#-karakteristik--keunggulan-implementasi-teknis)
5. [Panduan Instalasi & Menjalankan Proyek](#-panduan-instalasi--menjalankan-proyek)
6. [Panduan Pengujian Postman & Kamus Uji](#-panduan-pengujian-postman--kamus-uji)
7. [Galeri Bukti Pengujian Sistematis](#-galeri-bukti-pengujian-sistematis)
8. [Hasil Uji Otomatis (Automated Testing)](#-hasil-uji-otomatis-automated-testing)
9. [Riwayat Git Commit (Conventional Commits)](#-riwayat-git-commit-conventional-commits)

---

## 🏛️ Deskripsi & Evolusi Arsitektur

SIPUS-Del telah berevolusi dari aplikasi web monolitik berorientasi visual HTML (Minggu 5 & 6) menjadi arsitektur hibrida terdistribusi yang melayani komunikasi antar-mesin (*Machine-to-Machine* / M2M) melalui **SOAP XML** dan **RESTful JSON API v1**.

```
┌─────────────────────────────────────────────────────────────────────────────────┐
│              ARSITEKTUR TERDISTRIBUSI SIPUS-DEL (TAHAP 3 - MINGGU 9)            │
├─────────────────────────────────────────────────────────────────────────────────┤
│                                                                                 │
│   [ Sistem Legacy Yudisium ] ──(SOAP XML POST)──► [ LibrarySoapController ]    │
│                                                   (WSDL, Envelope, Fault)       │
│                                                                                 │
│   [ Aplikasi Mobile / Kiosk] ──(Bearer Token)───► [ routes/api.php (v1) ]       │
│                                                   (Sanctum, Stateless)          │
│                                                           │                     │
│                                                           ▼                     │
│                                                  [ API Controllers ]            │
│                                                   (ACID & lockForUpdate)        │
│                                                           │                     │
│                                                   ┌───────┴────────┐            │
│                                                   ▼                ▼            │
│                                            [ JsonResource ]  [ OpenLibrary ]    │
│                                            (HATEOAS Level 3)  (Http Consumer)   │
│                                                                                 │
└─────────────────────────────────────────────────────────────────────────────────┘
```

---

## 🔍 Analisis Kebutuhan & Kelayakan Web Services

### 1. Matriks Peran SIPUS-Del (Service Provider vs Service Consumer)

| Peran Arsitektural | Layanan / Endpoint | Protokol | Alasan & Konteks Bisnis |
|---|---|---|---|
| **Service Provider** | `POST /soap/sipus`<br>`GET /soap/sipus.wsdl` | SOAP over HTTP (XML) | Membuka kapabilitas pengecekan tanggungan buku (*Clearance Check*) untuk Sistem Administrasi Yudisium kampus versi lama yang masih mengikat kontrak formal WSDL. |
| **Service Provider** | `GET /api/v1/bukus`<br>`POST /api/v1/peminjamans`<br>`PATCH /api/v1/peminjamans/{id}/kembalikan` | RESTful API (JSON) | Menyediakan katalog buku dan sirkulasi peminjaman/pengembalian mandiri secara nir-status (*stateless*) bagi aplikasi Android Mahasiswa dan Kiosk Mandiri Perpustakaan. |
| **Service Consumer** | `GET /api/v1/bukus/{id}/referensi-eksternal` | RESTful API (JSON via Http Client) | Memanggil OpenLibrary REST API publik untuk memperkaya informasi detail buku (jumlah halaman, penerbit internasional, preview pratinjau) tanpa membebani database internal perpustakaan. |

### 2. Komparasi Kelayakan: Mengapa Memilih REST API untuk Mobile & Kiosk Dibanding SOAP?

| Parameter Kelayakan | SOAP (Simple Object Access Protocol) | REST (Representational State Transfer) | Justifikasi Pemilihan untuk SIPUS-Del Mobile/Kiosk |
|---|---|---|---|
| **Ukuran Payload (Overhead)** | Sangat besar (berat XML envelope, namespace verbose). | Sangat ringkas (JSON murni hingga 5–8x lebih hemat data). | **Pilihan: REST**. Menghemat kuota dan mempercepat *response time* pada perangkat mobile dengan sinyal berfluktuasi. |
| **Parsing & Performa CPU** | Butuh DOM/XML parser khusus yang rakus memori & CPU. | Didukung *native* oleh JavaScript, Kotlin, Swift, Flutter (`JSON.parse`). | **Pilihan: REST**. Sangat ramah baterai dan performa render aplikasi mobile. |
| **Semantik Operasi** | Hanya memakai metode HTTP POST sebagai pipa *tunneling*. | Memanfaatkan semantik HTTP murni (`GET`, `POST`, `PUT`, `PATCH`, `DELETE`). | **Pilihan: REST**. Operasi CRUD terpetakan alami, intuitif, dan memudahkan arsitektur caching HTTP. |
| **Kontrak & Fleksibilitas** | Terikat kaku pada file WSDL; perubahan sedikit merusak stub klien. | Fleksibel menggunakan OpenAPI 3.0 & JSONResource berlapis HATEOAS. | **Pilihan: REST**. Memudahkan iterasi penambahan fitur mobile tanpa memutus versi API lama. |

---

## 📋 Tabel Katalog Endpoint Web Services

### A. Layanan SOAP Legacy (Bebas Pustaka Yudisium)

| HTTP Verb | Endpoint URI | Proteksi Autentikasi | Deskripsi Operasi | Status Respon |
|---|---|---|---|---|
| `GET` | `/soap/sipus.wsdl` | Publik (Bebas CSRF) | Menyajikan dokumen kontrak WSDL XML untuk operasi `CheckClearanceStatus` | `200 OK` |
| `POST` | `/soap/sipus` | Publik (Bebas CSRF) | Menerima `<nim_peminjam>`, memeriksa riwayat buku berstatus `'dipinjam'`, mereturn status `<is_cleared>` | `200 OK` / `500 SOAP Fault` |

### B. Layanan RESTful Web API v1 (`/api/v1`)

| HTTP Verb | Endpoint URI | Proteksi Autentikasi | Deskripsi Operasi | Status Respon |
|---|---|---|---|---|
| `POST` | `/api/v1/auth/login` | Publik | Verifikasi email & password pustakawan, menerbitkan Sanctum Bearer Token | `200 OK`, `401 Unauthorized` |
| `GET` | `/api/v1/auth/me` | `Bearer Token` | Memeriksa profil pustakawan pemilik token aktif | `200 OK`, `401 Unauthorized` |
| `POST` | `/api/v1/auth/logout` | `Bearer Token` | Mencabut (*revoke*) token aktif dan mengakhiri sesi API | `200 OK`, `401 Unauthorized` |
| `GET` | `/api/v1/bukus` | Publik | Mengambil koleksi katalog buku terpaginasi (filter `search`, `kategori_id`, `tersedia`, `sort`, `dir`) + link HATEOAS | `200 OK` |
| `GET` | `/api/v1/bukus/{id}` | Publik | Mengambil detail satu buku (ID / ISBN) beserta relasi kategori dan riwayat peminjaman | `200 OK`, `404 Not Found` |
| `POST` | `/api/v1/bukus` | `Bearer Token` | Menambahkan buku baru ke basis data | `201 Created` + `Location`, `422 Unprocessable` |
| `PUT` | `/api/v1/bukus/{id}` | `Bearer Token` | Penggantian penuh (*Full Replacement*) seluruh atribut representasi buku | `200 OK`, `404 Not Found`, `422` |
| `PATCH` | `/api/v1/bukus/{id}` | `Bearer Token` | Pembaruan parsial (*Partial Update*) hanya atribut tertentu (misal: stok) | `200 OK`, `400 Bad Request`, `404` |
| `DELETE` | `/api/v1/bukus/{id}` | `Bearer Token` | Menghapus buku (ditolak jika sedang memiliki peminjaman aktif) | `204 No Content`, `409 Conflict` |
| `GET` | `/api/v1/bukus/{id}/referensi-eksternal` | Publik | Service Consumer: Mengambil metadata pratinjau buku dari OpenLibrary REST API | `200 OK`, `404`, `503 Unavailable` |
| `GET` | `/api/v1/peminjamans` | `Bearer Token` | Mengambil riwayat transaksi peminjaman terpaginasi (filter `status`, `nim`, `search`) | `200 OK`, `401 Unauthorized` |
| `POST` | `/api/v1/peminjamans` | `Bearer Token` | Transaksi ACID Peminjaman Buku dengan `lockForUpdate` (dekremen stok atomik) | `201 Created` + `Location`, `409`, `422` |
| `GET` | `/api/v1/peminjamans/{id}` | `Bearer Token` | Mengambil detail satu transaksi peminjaman | `200 OK`, `404 Not Found` |
| `PATCH` | `/api/v1/peminjamans/{id}/kembalikan` | `Bearer Token` | Transaksi ACID Pengembalian Buku dengan `lockForUpdate` (inkremen stok atomik) | `200 OK`, `409 Conflict`, `404` |

---

## 💡 Karakteristik & Keunggulan Implementasi Teknis

1. **Richardson Maturity Model (RMM) Level 3 (HATEOAS)**:
   - Pada `BukuResource`, kontrol `_links.pinjam_buku` **hanya dihadirkan apabila stok buku > 0**.
   - Pada `PeminjamanResource`, kontrol `_links.kembalikan_buku` **hanya dihadirkan apabila status transaksi masih `'dipinjam'`**.
2. **Standarisasi Galat IETF RFC 7807 (Problem Details for HTTP APIs)**:
   - Dikonfigurasikan terpusat di `bootstrap/app.php` menggunakan `shouldRenderJsonWhen` sehingga semua error `401`, `404`, `409`, dan `422` pada `/api/*` selalu mengembalikan header `Content-Type: application/problem+json` dengan struktur baku: `type`, `title`, `status`, `detail`, `instance`, dan `errors`.
3. **Pemberantasan N+1 Query Problem**:
   - Seluruh endpoint koleksi menggunakan eager loading `with('kategori')->withCount('peminjamans')` sehingga kueri tetap konstan 2 kali eksekusi berapa pun jumlah data yang dipaginasi.
4. **Jaminan Integritas Bisnis ACID & Pessimistic Locking**:
   - Operasi peminjaman dan pengembalian dibungkus `DB::transaction()` dengan `lockForUpdate()` untuk mencegah *race condition* atau over-borrowing pada pemesanan bersamaan.
5. **REST Service Consumer Tangguh**:
   - Pemanggilan OpenLibrary API dipagari dengan `Http::timeout(8)` serta blok *fallback graceful* jika jaringan eksternal bermasalah.

---

## 🚀 Panduan Instalasi & Menjalankan Proyek

### 1. Klon Repositori & Masuk ke Branch
```bash
git clone <URL_REPOSITORI_ANDA>
cd P05_12S24035_JOSUA
git checkout week9-rest-api
```

### 2. Instalasi Dependensi
```bash
composer install
npm install && npm run build
```

### 3. Konfigurasi Lingkungan `.env`
Salin berkas `.env.example` ke `.env` (pastikan kredensial database sesuai dengan Laragon/MySQL lokal):
```bash
cp .env.example .env
php artisan key:generate
```

### 4. Migrasi Skema & Suntik Data Awal
```bash
php artisan migrate:fresh --seed
```
> **Akun Pengujian Pustakawan Default:**  
> - **Email**: `pustakawan@del.ac.id`  
> - **Password**: `password123`

### 5. Jalankan Server Lokal
```bash
php artisan serve
```
Aplikasi API aktif dan dapat diakses di: **`http://localhost:8000/api/v1`**  
Layanan SOAP WSDL aktif di: **`http://localhost:8000/soap/sipus.wsdl`**

---

## 🧪 Panduan Pengujian Postman & Kamus Uji

Untuk menguji seluruh endpoint secara instan tanpa perlu merakit URL secara manual:
1. Buka aplikasi **Postman Desktop**.
2. Klik tombol **Import** di kiri atas, lalu pilih berkas:
   ```
   docs/SIPUS-Del-API-v1.postman_collection.json
   ```
3. Koleksi telah dilengkapi variabel:
   - `{{base_url}}` = `http://localhost:8000/api/v1`
   - `{{token}}` = Otomatis terisi saat menjalankan request login!
4. Kontrak formal OpenAPI 3.0 juga dapat dilihat dan divalidasi pada:
   ```
   docs/openapi-sipus-v1.yaml
   ```
   *(Dapat diimpor langsung ke [editor.swagger.io](https://editor.swagger.io))*.

---

## 📸 Galeri Bukti Pengujian Sistematis

> *Catatan: Silakan jalankan koleksi Postman di komputer lokal Anda dan simpan tangkapan layar ke folder `docs/screenshots/` untuk melengkapi galeri di bawah ini.*

### 1. Uji Layanan SOAP XML & SOAP Fault
- **(a) Skenario Normal (Bebas Pustaka - is_cleared true):**
  ![SOAP Normal](docs/screenshots/01_soap_normal.png)
- **(b) Skenario SOAP Fault (NIM Kosong / Malformed XML):**
  ![SOAP Fault](docs/screenshots/02_soap_fault.png)

### 2. Uji Otentikasi Stateless Sanctum (Login & Bearer Token)
- **Login Berhasil & Menerbitkan Access Token:**
  ![Sanctum Login](docs/screenshots/03_sanctum_login.png)
- **Uji 401 Unauthorized Tanpa Token:**
  ![Sanctum 401](docs/screenshots/04_sanctum_401.png)

### 3. Uji GET Katalog Terpaginasi & HATEOAS Level 3
- **Koleksi Buku dengan Metadata Paginasi & Link Dinamis:**
  ![Katalog HATEOAS](docs/screenshots/05_katalog_hateoas.png)

### 4. Uji POST Buku Baru (201 Created + Header Location)
- **Penambahan Buku Baru Berhasil:**
  ![POST Buku 201](docs/screenshots/06_post_buku_location.png)

### 5. Uji Pembuktian Semantik PUT vs PATCH
- **(a) Semantik PATCH (Hanya Mengubah Stok):**
  ![PATCH Stok](docs/screenshots/07_patch_buku.png)
- **(b) Semantik PUT (Penggantian Penuh & Reset Atribut Kosong):**
  ![PUT Full](docs/screenshots/08_put_buku.png)

### 6. Uji Transaksi Sirkulasi Peminjaman ACID
- **(a) Peminjaman Sukses & Dekremen Stok Atomik (201 Created):**
  ![Peminjaman Sukses](docs/screenshots/09_peminjaman_sukses.png)
- **(b) Penolakan Stok Habis (422 Unprocessable Content):**
  ![Stok Habis 422](docs/screenshots/10_stok_habis_422.png)
- **(c) Penolakan Peminjaman Duplikat (409 Conflict):**
  ![Duplikat Pinjam 409](docs/screenshots/11_duplikat_pinjam_409.png)
- **(d) Pengembalian Buku & Inkremen Stok Atomik (200 OK):**
  ![Kembalikan Buku](docs/screenshots/12_kembalikan_sukses.png)

### 7. Uji REST Service Consumer (OpenLibrary API via Http Facade)
- **Pengambilan Metadata Eksternal Berdasarkan ISBN:**
  ![OpenLibrary Consumer](docs/screenshots/13_openlibrary_consumer.png)

---

## ⚡ Hasil Uji Otomatis (Automated Testing)

Semua skenario pengujian unit (*Unit Tests*) dan fitur (*Feature Tests*) berhasil dilewati dengan sempurna (**100% PASS**):

```
   PASS  Tests\Unit\ExampleTest
  ✓ that true is true

   PASS  Tests\Feature\ExampleTest
  ✓ the application returns a successful response                                 0.38s  

   PASS  Tests\Feature\PeminjamanTransactionTest
  ✓ peminjaman berhasil mengurangi stok secara atomik                             0.20s  
  ✓ penolakan transaksi peminjaman saat stok habis                                0.03s  
  ✓ penolakan peminjaman ganda oleh nim yang sama                                 0.04s  
  ✓ pengembalian buku berhasil menambah stok kembali                              0.04s  

   PASS  Tests\Feature\SipusDelApiTest
  ✓ soap wsdl endpoint is accessible                                              0.07s  
  ✓ soap endpoint returns cleared when no active loans                            0.03s  
  ✓ soap endpoint returns not cleared when active loan exists                     0.03s  
  ✓ soap endpoint returns fault for empty or malformed xml                        0.03s  
  ✓ auth login issues bearer token                                                0.05s  
  ✓ auth login fails with invalid credentials                                     0.03s  
  ✓ auth me and logout endpoints                                                  0.04s  
  ✓ get bukus collection with hateoas                                             0.04s  
  ✓ hateoas pinjam buku disappears when stock is zero                             0.03s  
  ✓ create buku requires token and returns 201 with location header               0.09s  
  ✓ put vs patch buku semantics                                                   0.05s  
  ✓ delete buku rejected when active loan exists                                  0.04s  
  ✓ peminjaman and return acid lifecycle                                          0.06s  
  ✓ external book reference consumer endpoint                                     0.08s  

   PASS  Tests\Feature\SipusDelTest
  ✓ root redirects to buku index                                                  0.04s  
  ✓ can view buku index with eager loading                                        0.04s  
  ✓ validation prevents duplicate isbn and short title                            0.03s  
  ✓ can create new buku and kategori                                              0.03s  
  ✓ katalog buku displays query builder statistics and withcount peminjamans      0.03s  
  ✓ katalog buku dynamic search and session state                                 0.04s  

  Tests:    26 passed (124 assertions)
  Duration: 2.10s
```

---

## 📜 Riwayat Git Commit (Conventional Commits)

Implementasi dikomit secara bertahap, bersih, dan bermakna mengikuti spesifikasi Modul Bagian 6.1:

```
* feat(api-consumer): integrate external book metadata lookup via Laravel Http client
* feat(api-loans): implement ACID book borrowing and return REST endpoints with lockForUpdate
* feat(api-books): build BukuApiController and BukuResource with dynamic HATEOAS links
* feat(api-auth): implement stateless Sanctum bearer token login and revocation
* feat(api-core): install laravel api routing, ApiResponse trait, and RFC 7807 exception handler
* feat(soap): add concise LibrarySoapController and WSDL contract for clearance check
* docs(contract): add OpenAPI 3.0 specification, Postman collection, and test report in README
```

---
*Dibuat oleh **Josua Sianturi (12S24035)** untuk memenuhi Tugas Praktikum Minggu 09 Mata Kuliah Pemrograman & Pengujian Web — Institut Teknologi Del.*
