<?php

namespace Tests\Feature;

use App\Models\Buku;
use App\Models\Kategori;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SipusDelTest extends TestCase
{
    use RefreshDatabase;

    public function test_root_redirects_to_buku_index(): void
    {
        $response = $this->get('/');
        $response->assertRedirect(route('buku.index'));
    }

    public function test_can_view_buku_index_with_eager_loading(): void
    {
        $kategori = Kategori::create([
            'kode_kategori' => 'RPL',
            'nama_kategori' => 'Rekayasa Perangkat Lunak',
        ]);

        Buku::create([
            'isbn' => '978-602-001-001-1',
            'judul' => 'Pemrograman Web dengan Laravel 11',
            'penulis' => 'Tim Dosen Del',
            'penerbit' => 'Del Press',
            'tahun_terbit' => 2026,
            'kategori_id' => $kategori->id,
            'stok' => 10,
            'sinopsis' => 'Buku pegangan mahasiswa mata kuliah PPW.',
        ]);

        $response = $this->get(route('buku.index'));

        $response->assertStatus(200);
        $response->assertSee('Pemrograman Web dengan Laravel 11');
        $response->assertSee('Rekayasa Perangkat Lunak');
    }

    public function test_validation_prevents_duplicate_isbn_and_short_title(): void
    {
        $kategori = Kategori::create([
            'kode_kategori' => 'INF',
            'nama_kategori' => 'Informatika',
        ]);

        Buku::create([
            'isbn' => '978-111-222-333-4',
            'judul' => 'Struktur Data Dasar',
            'penulis' => 'Penulis Asli',
            'penerbit' => 'Del Press',
            'tahun_terbit' => 2025,
            'kategori_id' => $kategori->id,
            'stok' => 5,
        ]);

        // Uji judul kurang dari 5 karakter & ISBN duplikat
        $response = $this->post(route('buku.store'), [
            'isbn' => '978-111-222-333-4',
            'judul' => 'Abc', // kurang dari 5 karakter
            'penulis' => 'Penulis Uji',
            'penerbit' => 'Penerbit Uji',
            'tahun_terbit' => 2026,
            'kategori_id' => $kategori->id,
            'stok' => 2,
        ]);

        $response->assertSessionHasErrors(['isbn', 'judul']);
    }

    public function test_can_create_new_buku_and_kategori(): void
    {
        $kategori = Kategori::create([
            'kode_kategori' => 'SI',
            'nama_kategori' => 'Sistem Informasi',
        ]);

        $response = $this->post(route('buku.store'), [
            'isbn' => '978-999-888-777-6',
            'judul' => 'Enterprise Architecture Planning',
            'penulis' => 'Dr. John Doe',
            'penerbit' => 'Andi Offset',
            'tahun_terbit' => 2024,
            'kategori_id' => $kategori->id,
            'stok' => 15,
            'sinopsis' => 'Buku perencanaan sistem enterprise.',
        ]);

        $response->assertRedirect(route('buku.index'));
        $this->assertDatabaseHas('bukus', [
            'isbn' => '978-999-888-777-6',
            'judul' => 'Enterprise Architecture Planning',
        ]);
    }

    public function test_katalog_buku_displays_query_builder_statistics_and_withcount_peminjamans(): void
    {
        $kategori = Kategori::create([
            'kode_kategori' => 'AI',
            'nama_kategori' => 'Artificial Intelligence',
        ]);

        $buku = Buku::create([
            'isbn' => '978-123-456-789-0',
            'judul' => 'Deep Learning Foundation',
            'penulis' => 'Andrew Ng',
            'penerbit' => 'MIT Press',
            'tahun_terbit' => 2024,
            'kategori_id' => $kategori->id,
            'stok' => 8,
            'sinopsis' => 'Buku dasar deep learning.',
        ]);

        $response = $this->get(route('buku.index'));
        $response->assertStatus(200);
        $response->assertViewHas('stats');
        $response->assertSee('Total Judul Buku');
        $response->assertSee('Total Stok Tersedia');
        $response->assertSee('0x dipinjam');
    }

    public function test_katalog_buku_dynamic_search_and_session_state(): void
    {
        $kategori = Kategori::create([
            'kode_kategori' => 'SEC',
            'nama_kategori' => 'Cyber Security',
        ]);

        Buku::create([
            'isbn' => '978-777-666-555-4',
            'judul' => 'Ethical Hacking & Defense',
            'penulis' => 'Kevin Mitnick',
            'penerbit' => 'Wiley',
            'tahun_terbit' => 2025,
            'kategori_id' => $kategori->id,
            'stok' => 4,
        ]);

        $response = $this->get(route('buku.index', ['search' => 'Hacking']));
        $response->assertStatus(200);
        $response->assertSee('Ethical Hacking & Defense');
        $this->assertEquals('Hacking', session('last_buku_search'));
    }
}
