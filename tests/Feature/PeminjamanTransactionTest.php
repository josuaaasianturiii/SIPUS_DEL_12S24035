<?php

namespace Tests\Feature;

use App\Models\Buku;
use App\Models\Kategori;
use App\Models\Peminjaman;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PeminjamanTransactionTest extends TestCase
{
    use RefreshDatabase;

    private function createBuku(int $stok = 3): Buku
    {
        $kategori = Kategori::firstOrCreate(
            ['kode_kategori' => 'TEST'],
            ['nama_kategori' => 'Kategori Test']
        );

        return Buku::create([
            'isbn' => '978-' . rand(100000000, 999999999),
            'judul' => 'Buku Transaksi ACID ' . uniqid(),
            'penulis' => 'Penulis Del',
            'penerbit' => 'Del Press',
            'tahun_terbit' => 2026,
            'kategori_id' => $kategori->id,
            'stok' => $stok,
            'sinopsis' => 'Buku pengujian transaksi atomik ACID dan lockForUpdate',
        ]);
    }

    /**
     * Uji 1: Peminjaman normal berhasil mengurangi stok dan mencatat peminjaman secara atomik.
     */
    public function test_peminjaman_berhasil_mengurangi_stok_secara_atomik(): void
    {
        $buku = $this->createBuku(5);
        $initialStok = $buku->stok;

        $response = $this->post(route('peminjaman.store'), [
            'buku_id' => $buku->id,
            'nim_peminjam' => '12S24035',
            'nama_peminjam' => 'Josua Sianturi',
            'tanggal_pinjam' => now()->toDateString(),
            'tanggal_jatuh_tempo' => now()->addDays(7)->toDateString(),
        ]);

        $response->assertRedirect(route('peminjaman.index'));
        $response->assertSessionHas('success');

        // Pastikan stok buku berkurang tepat 1
        $this->assertEquals($initialStok - 1, $buku->fresh()->stok);

        // Pastikan data tersimpan di tabel peminjamans
        $this->assertDatabaseHas('peminjamans', [
            'buku_id' => $buku->id,
            'nim_peminjam' => '12S24035',
            'status' => 'dipinjam',
        ]);
    }

    /**
     * Uji 2: Penolakan dan rollback transaksi saat stok buku habis (stok = 0).
     */
    public function test_penolakan_transaksi_peminjaman_saat_stok_habis(): void
    {
        $buku = $this->createBuku(0); // Stok 0

        $response = $this->post(route('peminjaman.store'), [
            'buku_id' => $buku->id,
            'nim_peminjam' => '12S24035',
            'nama_peminjam' => 'Josua Sianturi',
            'tanggal_pinjam' => now()->toDateString(),
            'tanggal_jatuh_tempo' => now()->addDays(7)->toDateString(),
        ]);

        $response->assertSessionHas('error');

        // Pastikan stok tetap 0 (tidak minus)
        $this->assertEquals(0, $buku->fresh()->stok);

        // Pastikan tidak ada data yang masuk ke peminjamans
        $this->assertDatabaseMissing('peminjamans', [
            'buku_id' => $buku->id,
            'nim_peminjam' => '12S24035',
        ]);
    }

    /**
     * Uji 3: Penolakan peminjaman jika NIM yang sama belum mengembalikan buku tersebut.
     */
    public function test_penolakan_peminjaman_ganda_oleh_nim_yang_sama(): void
    {
        $buku = $this->createBuku(5);

        // Peminjaman pertama
        $this->post(route('peminjaman.store'), [
            'buku_id' => $buku->id,
            'nim_peminjam' => '12S24035',
            'nama_peminjam' => 'Josua Sianturi',
            'tanggal_pinjam' => now()->toDateString(),
            'tanggal_jatuh_tempo' => now()->addDays(7)->toDateString(),
        ]);

        $stokSetelahPinjam1 = $buku->fresh()->stok;

        // Peminjaman kedua buku yang sama oleh NIM yang sama
        $response = $this->post(route('peminjaman.store'), [
            'buku_id' => $buku->id,
            'nim_peminjam' => '12S24035',
            'nama_peminjam' => 'Josua Sianturi',
            'tanggal_pinjam' => now()->toDateString(),
            'tanggal_jatuh_tempo' => now()->addDays(7)->toDateString(),
        ]);

        $response->assertSessionHas('error');

        // Pastikan stok tidak berkurang lagi
        $this->assertEquals($stokSetelahPinjam1, $buku->fresh()->stok);
    }

    /**
     * Uji 4: Pengembalian buku berhasil mengubah status dan menambah stok kembali.
     */
    public function test_pengembalian_buku_berhasil_menambah_stok_kembali(): void
    {
        $buku = $this->createBuku(3);

        $this->post(route('peminjaman.store'), [
            'buku_id' => $buku->id,
            'nim_peminjam' => '12S24099',
            'nama_peminjam' => 'Budi Siregar',
            'tanggal_pinjam' => now()->toDateString(),
            'tanggal_jatuh_tempo' => now()->addDays(7)->toDateString(),
        ]);

        $peminjaman = Peminjaman::where('buku_id', $buku->id)->where('status', 'dipinjam')->first();
        $stokDipinjam = $buku->fresh()->stok; // Stok = 2

        $response = $this->post(route('peminjaman.kembalikan', $peminjaman->id));
        $response->assertSessionHas('success');

        // Pastikan status menjadi 'dikembalikan' dan tanggal_kembali terisi
        $peminjamanFresh = $peminjaman->fresh();
        $this->assertEquals('dikembalikan', $peminjamanFresh->status);
        $this->assertNotNull($peminjamanFresh->tanggal_kembali);

        // Pastikan stok kembali bertambah 1
        $this->assertEquals($stokDipinjam + 1, $buku->fresh()->stok);
    }
}
