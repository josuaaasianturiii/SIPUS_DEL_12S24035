<?php

namespace Database\Factories;

use App\Models\Kategori;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Kategori>
 */
class KategoriFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'kode_kategori' => strtoupper(fake()->unique()->bothify('??#')),
            'nama_kategori' => fake()->unique()->randomElement([
                'Rekayasa Perangkat Lunak',
                'Sains Data & Kecerdasan Buatan',
                'Jaringan Komputer & Keamanan Siber',
                'Sistem Informasi & Manajemen',
                'Matematika & Teori Komputasi',
                'Pengembangan Web Modern',
                'Algoritma & Struktur Data',
                'Basis Data & Cloud Computing',
            ]),
        ];
    }
}
