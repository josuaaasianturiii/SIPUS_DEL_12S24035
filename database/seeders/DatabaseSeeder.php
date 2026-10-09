<?php

namespace Database\Seeders;

use App\Models\Buku;
use App\Models\Kategori;
use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // 1. Buat Kategori Master Standar
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
            $kategoris->push(Kategori::create($item));
        }

        // 2. Buat Minimal 25 Buku Realistis menggunakan Factory & Faker
        foreach ($kategoris as $kategori) {
            // Setiap kategori memiliki minimal 4 - 6 buku (total > 25 buku)
            Buku::factory()->count(rand(4, 6))->create([
                'kategori_id' => $kategori->id,
            ]);
        }
    }
}
