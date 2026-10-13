<?php

namespace App\Http\Controllers;

use App\Models\Buku;
use App\Models\Kategori;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class BukuController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        // Simpan riwayat pencarian terakhir ke sesi (Session State)
        if ($request->filled('search')) {
            session(['last_buku_search' => $request->search]);
        }

        $search = $request->query('search');
        $kategoriId = $request->query('kategori_id');

        // Eloquent Eager Loading relasi kategori & withCount peminjamans (Bebas N+1 Problem)
        // Filter dinamis dengan when() dan paginasi withQueryString()
        $bukus = Buku::with('kategori')
            ->withCount('peminjamans')
            ->when($search, function ($query, $s) {
                $query->where(function ($q) use ($s) {
                    $q->where('judul', 'like', "%{$s}%")
                      ->orWhere('penulis', 'like', "%{$s}%")
                      ->orWhere('isbn', 'like', "%{$s}%");
                });
            })
            ->when($kategoriId, fn($query, $kId) => $query->where('kategori_id', $kId))
            ->latest()
            ->paginate(10)
            ->withQueryString();

        // Rekapitulasi Statistik Perpustakaan Cepat menggunakan Fluent Query Builder (DB::table)
        $stats = [
            'total_judul' => DB::table('bukus')->count(),
            'total_stok' => (int) DB::table('bukus')->sum('stok'),
            'total_dipinjam' => DB::table('peminjamans')->where('status', 'dipinjam')->count(),
            'total_dikembalikan' => DB::table('peminjamans')->where('status', 'dikembalikan')->count(),
        ];

        $kategoris = Kategori::orderBy('nama_kategori')->get();

        return view('buku.index', compact('bukus', 'stats', 'kategoris'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        $kategoris = Kategori::orderBy('nama_kategori')->get();
        return view('buku.create', compact('kategoris'));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $currentYear = (int) date('Y') + 1;
        $validated = $request->validate([
            'isbn' => 'required|string|min:5|max:20|unique:bukus,isbn',
            'judul' => 'required|string|min:5|max:255',
            'penulis' => 'required|string|min:3|max:150',
            'penerbit' => 'required|string|min:3|max:150',
            'tahun_terbit' => "required|integer|min:1900|max:{$currentYear}",
            'kategori_id' => 'required|exists:kategoris,id',
            'stok' => 'required|integer|min:0',
            'sinopsis' => 'nullable|string',
        ]);

        Buku::create($validated);

        return redirect()->route('buku.index')->with('success', 'Buku baru berhasil ditambahkan!');
    }

    /**
     * Display the specified resource.
     */
    public function show(Buku $buku)
    {
        $buku->load(['kategori', 'peminjamans' => fn($q) => $q->latest()]);
        return view('buku.show', compact('buku'));
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Buku $buku)
    {
        $kategoris = Kategori::orderBy('nama_kategori')->get();
        return view('buku.edit', compact('buku', 'kategoris'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Buku $buku)
    {
        $currentYear = (int) date('Y') + 1;
        $validated = $request->validate([
            'isbn' => ['required', 'string', 'min:5', 'max:20', Rule::unique('bukus', 'isbn')->ignore($buku->id)],
            'judul' => 'required|string|min:5|max:255',
            'penulis' => 'required|string|min:3|max:150',
            'penerbit' => 'required|string|min:3|max:150',
            'tahun_terbit' => "required|integer|min:1900|max:{$currentYear}",
            'kategori_id' => 'required|exists:kategoris,id',
            'stok' => 'required|integer|min:0',
            'sinopsis' => 'nullable|string',
        ]);

        $buku->update($validated);

        return redirect()->route('buku.index')->with('success', 'Data buku berhasil diperbarui!');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Buku $buku)
    {
        $judul = $buku->judul;
        $buku->delete();

        return redirect()->route('buku.index')->with('success', "Buku '{$judul}' berhasil dihapus!");
    }
}
