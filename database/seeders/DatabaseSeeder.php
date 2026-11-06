<?php

namespace Database\Seeders;

use App\Models\Buku;
use App\Models\Kategori;
use App\Models\Peminjaman;
use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // 1. Akun Pustakawan Uji (Sesuai Spesifikasi Teknis Wajib Modul Minggu 6)
        $pustakawan = User::firstOrCreate(
            ['email' => 'pustakawan@del.ac.id'],
            [
                'name' => 'Pustakawan Institut Teknologi Del',
                'password' => Hash::make('password123'),
                'email_verified_at' => now(),
            ]
        );

        // 2. Kategori Master Standar
        $kategoriList = [
            ['kode_kategori' => 'RPL', 'nama_kategori' => 'Rekayasa Perangkat Lunak & Web'],
            ['kode_kategori' => 'AI-DS', 'nama_kategori' => 'Artificial Intelligence & Data Science'],
            ['kode_kategori' => 'CYBER', 'nama_kategori' => 'Keamanan Siber & Jaringan Komputer'],
            ['kode_kategori' => 'SI', 'nama_kategori' => 'Sistem Informasi & Manajemen Bisnis'],
            ['kode_kategori' => 'DB-CLOUD', 'nama_kategori' => 'Basis Data & Arsitektur Cloud'],
            ['kode_kategori' => 'MAT-KOMP', 'nama_kategori' => 'Matematika & Logika Komputasi'],
        ];

        $kategoris = collect();
        foreach ($kategoriList as $item) {
            $kategoris->push(Kategori::firstOrCreate(['kode_kategori' => $item['kode_kategori']], $item));
        }

        $rpl = $kategoris->firstWhere('kode_kategori', 'RPL');
        $cyber = $kategoris->firstWhere('kode_kategori', 'CYBER');

        // 3. Sampel Buku Khusus: Stok = 0 (Uji Tolak & Rollback saat Stok Habis)
        $bukuStokNol = Buku::firstOrCreate(
            ['isbn' => '978-602-000-001-0'],
            [
                'judul' => 'Struktur Data & Algoritma Lanjut (Sampel Stok Habis)',
                'penulis' => 'Dr. Arnaldo Marulitua Sinaga',
                'penerbit' => 'Del Press',
                'tahun_terbit' => 2024,
                'kategori_id' => $rpl->id,
                'stok' => 0,
                'sinopsis' => 'Buku sampel khusus pengujian penolakan transaksi peminjaman saat kuota stok habis (stok = 0).',
            ]
        );

        // 4. Sampel Buku Khusus: Stok = 1 (Uji Transaksi Atomik & Concurrency lockForUpdate)
        $bukuStokSatu = Buku::firstOrCreate(
            ['isbn' => '978-602-000-002-7'],
            [
                'judul' => 'Sistem Operasi & Concurrency Multi-Threading (Sampel Stok Kritis)',
                'penulis' => 'Chandro Pardede, S.Kom., M.Sc.',
                'penerbit' => 'Del Press',
                'tahun_terbit' => 2025,
                'kategori_id' => $cyber->id,
                'stok' => 1,
                'sinopsis' => 'Buku sampel khusus pengujian Pessimistic Locking (lockForUpdate) saat sisa stok tinggal 1 eksemplar.',
            ]
        );

        // 5. Sampel Buku Tambahan (Minimal 25 buku)
        foreach ($kategoris as $kategori) {
            Buku::factory()->count(4)->create([
                'kategori_id' => $kategori->id,
            ]);
        }

        // 6. Sampel Transaksi Peminjaman (Dipinjam & Dikembalikan)
        // a. Sampel Transaksi Aktif ('dipinjam')
        Peminjaman::firstOrCreate(
            ['kode_pinjam' => 'PJM-20261013-00001'],
            [
                'buku_id' => $bukuStokSatu->id,
                'nim_peminjam' => '12S24035',
                'nama_peminjam' => 'Josua Sianturi',
                'tanggal_pinjam' => now()->subDays(2)->toDateString(),
                'tanggal_jatuh_tempo' => now()->addDays(5)->toDateString(),
                'status' => 'dipinjam',
            ]
        );

        // b. Sampel Transaksi Selesai ('dikembalikan')
        Peminjaman::firstOrCreate(
            ['kode_pinjam' => 'PJM-20261013-00002'],
            [
                'buku_id' => $bukuStokNol->id,
                'nim_peminjam' => '12S24001',
                'nama_peminjam' => 'Martua Simanjuntak',
                'tanggal_pinjam' => now()->subDays(10)->toDateString(),
                'tanggal_jatuh_tempo' => now()->subDays(3)->toDateString(),
                'tanggal_kembali' => now()->subDays(4)->toDateString(),
                'status' => 'dikembalikan',
            ]
        );
    }
}
