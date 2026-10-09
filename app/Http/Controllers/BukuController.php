<?php

namespace App\Http\Controllers;

use App\Models\Buku;
use App\Models\Kategori;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class BukuController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        // Eager Loading relasi kategori untuk mencegah N+1 Query Problem
        $bukus = Buku::with('kategori')->latest()->paginate(10);
        return view('buku.index', compact('bukus'));
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
        $buku->load('kategori');
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
