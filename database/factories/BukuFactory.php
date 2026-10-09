<?php

namespace Database\Factories;

use App\Models\Buku;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Buku>
 */
class BukuFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $prefixes = ['Panduan Praktis', 'Mastering', 'Kupas Tuntas', 'Dasar-Dasar', 'Arsitektur & Desain', 'Implementasi Modern', 'Teori & Aplikasi', 'Strategi Lanjut'];
        $topics = ['Laravel 11 & PHP 8', 'Clean Architecture & Testing', 'Basis Data Relasional & NoSQL', 'Machine Learning dengan Python', 'Keamanan Siber & Ethical Hacking', 'RESTful API & Microservices', 'Algoritma & Struktur Data Lanjut', 'Cloud Computing & Docker DevOps', 'Sistem Informasi Manajemen', 'Desain Interaksi UI/UX'];

        return [
            'isbn' => fake()->unique()->numerify('978-602-###-###-#'),
            'judul' => fake()->randomElement($prefixes) . ' ' . fake()->randomElement($topics),
            'penulis' => fake()->name(),
            'penerbit' => fake()->randomElement(['Informatika Bandung', 'Andi Offset', 'Elex Media Komputindo', 'Del Press', 'Gava Media', 'Salemba Infotek']),
            'tahun_terbit' => fake()->numberBetween(2018, (int) date('Y')),
            'kategori_id' => \App\Models\Kategori::factory(),
            'stok' => fake()->numberBetween(0, 25),
            'sinopsis' => fake()->paragraphs(2, true),
        ];
    }
}
