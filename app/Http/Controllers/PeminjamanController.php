<?php

namespace App\Http\Controllers;

use App\Models\Buku;
use App\Models\Peminjaman;
use Illuminate\Database\QueryException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cookie;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class PeminjamanController extends Controller
{
    /**
     * Tampilkan daftar sirkulasi peminjaman buku.
     */
    public function index(Request $request)
    {
        $status = $request->query('status');
        $search = $request->query('search');

        // Sesi pencarian sirkulasi
        if ($request->filled('search')) {
            session(['last_peminjaman_search' => $search]);
        }

        $peminjamans = Peminjaman::with('buku')
            ->when($status, fn($q, $s) => $q->where('status', $s))
            ->when($search, function ($q, $s) {
                $q->where(function ($query) use ($s) {
                    $query->where('kode_pinjam', 'like', "%{$s}%")
                        ->orWhere('nim_peminjam', 'like', "%{$s}%")
                        ->orWhere('nama_peminjam', 'like', "%{$s}%")
                        ->orWhereHas('buku', fn($b) => $b->where('judul', 'like', "%{$s}%"));
                });
            })
            ->latest()
            ->paginate(10)
            ->withQueryString();

        return view('peminjaman.index', compact('peminjamans'));
    }

    /**
     * Tampilkan form pencatatan peminjaman baru.
     */
    public function create(Request $request)
    {
        $selectedBukuId = $request->query('buku_id');
        $bukus = Buku::where('stok', '>', 0)->orderBy('judul')->get();
        
        // Membaca cookie atau session peminjam terakhir untuk kemudahan pengguna
        $lastNim = $request->cookie('last_peminjam_nim') ?? session('last_peminjam_nim', '');
        $lastNama = session('last_peminjam_nama', '');

        return view('peminjaman.create', compact('bukus', 'selectedBukuId', 'lastNim', 'lastNama'));
    }

    /**
     * Transaksi ACID Peminjaman Buku dengan lockForUpdate.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'buku_id' => 'required|exists:bukus,id',
            'nim_peminjam' => 'required|string|min:6|max:15',
            'nama_peminjam' => 'required|string|min:3|max:150',
            'tanggal_pinjam' => 'required|date',
            'tanggal_jatuh_tempo' => 'required|date|after_or_equal:tanggal_pinjam',
        ]);

        $nim = strtoupper(trim($validated['nim_peminjam']));
        $nama = trim($validated['nama_peminjam']);

        try {
            // Bungkus seluruh operasi multi-tulis ke dalam DB::transaction()
            $peminjaman = DB::transaction(function () use ($validated, $nim, $nama) {
                // 1. Pessimistic Locking pada baris Buku yang akan dipinjam
                $buku = Buku::where('id', $validated['buku_id'])
                    ->lockForUpdate()
                    ->firstOrFail();

                // 2. Pemeriksaan sisa stok buku > 0 (Rollback jika stok = 0)
                if ($buku->stok < 1) {
                    throw new \RuntimeException("Transaksi Ditolak: Stok buku '{$buku->judul}' telah habis (stok = 0)!");
                }

                // 3. Mencegah NIM yang sama meminjam buku yang sama yang belum dikembalikan
                $masihDipinjam = Peminjaman::where('buku_id', $buku->id)
                    ->where('nim_peminjam', $nim)
                    ->where('status', 'dipinjam')
                    ->lockForUpdate()
                    ->exists();

                if ($masihDipinjam) {
                    throw new \RuntimeException("Transaksi Ditolak: Mahasiswa NIM {$nim} saat ini masih meminjam buku ini dan belum mengembalikannya.");
                }

                // 4. Generate kode pinjam unik
                $kodePinjam = 'PJM-' . date('Ymd') . '-' . strtoupper(Str::random(5));

                // 5. Kurangi stok buku secara atomik
                $buku->decrement('stok');

                // 6. Catat riwayat sirkulasi ke tabel peminjamans
                return Peminjaman::create([
                    'kode_pinjam' => $kodePinjam,
                    'buku_id' => $buku->id,
                    'nim_peminjam' => $nim,
                    'nama_peminjam' => $nama,
                    'tanggal_pinjam' => $validated['tanggal_pinjam'],
                    'tanggal_jatuh_tempo' => $validated['tanggal_jatuh_tempo'],
                    'status' => 'dipinjam',
                ]);
            }, 3);

            // Simpan Session State dan Cookie
            session([
                'last_peminjam_nim' => $nim,
                'last_peminjam_nama' => $nama,
            ]);
            Cookie::queue('last_peminjam_nim', $nim, 10080); // Cookie aktif 7 hari (10080 menit)

            return redirect()->route('peminjaman.index')
                ->with('success', "Peminjaman buku '{$peminjaman->buku->judul}' berhasil dicatat dengan Kode: {$peminjaman->kode_pinjam}!");

        } catch (\RuntimeException $e) {
            return back()->withInput()->with('error', $e->getMessage());
        } catch (QueryException $e) {
            Log::error('[DB EXCEPTION - PEMINJAMAN]: ' . $e->getMessage());
            return back()->withInput()->with('error', 'Terjadi kesalahan integritas basis data saat memproses peminjaman.');
        }
    }

    /**
     * Transaksi ACID Pengembalian Buku dengan lockForUpdate.
     */
    public function kembalikan(Request $request, int $id)
    {
        try {
            DB::transaction(function () use ($id) {
                // 1. Kunci baris peminjaman
                $peminjaman = Peminjaman::where('id', $id)
                    ->lockForUpdate()
                    ->firstOrFail();

                if ($peminjaman->status === 'dikembalikan') {
                    throw new \RuntimeException("Buku dengan kode peminjaman {$peminjaman->kode_pinjam} sudah pernah dikembalikan.");
                }

                // 2. Kunci baris buku terkait
                $buku = Buku::where('id', $peminjaman->buku_id)
                    ->lockForUpdate()
                    ->firstOrFail();

                // 3. Ubah status menjadi 'dikembalikan' dan isi tanggal_kembali
                $peminjaman->update([
                    'status' => 'dikembalikan',
                    'tanggal_kembali' => now()->toDateString(),
                ]);

                // 4. Kembalikan stok buku secara atomik
                $buku->increment('stok');
            }, 3);

            return back()->with('success', 'Buku berhasil dikembalikan dan stok buku telah ditambahkan kembali!');

        } catch (\RuntimeException $e) {
            return back()->with('error', $e->getMessage());
        } catch (QueryException $e) {
            Log::error('[DB EXCEPTION - PENGEMBALIAN]: ' . $e->getMessage());
            return back()->with('error', 'Terjadi kesalahan sistem saat memproses pengembalian buku.');
        }
    }
}
