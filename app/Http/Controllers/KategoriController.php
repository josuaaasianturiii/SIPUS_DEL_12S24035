<?php

namespace App\Http\Controllers;

use App\Models\Kategori;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class KategoriController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $kategoris = Kategori::withCount('bukus')->latest()->paginate(10);
        return view('kategori.index', compact('kategoris'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        return view('kategori.create');
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'kode_kategori' => 'required|string|max:10|unique:kategoris,kode_kategori',
            'nama_kategori' => 'required|string|min:3|max:100',
        ]);

        Kategori::create($validated);

        return redirect()->route('kategori.index')->with('success', 'Kategori baru berhasil ditambahkan!');
    }

    /**
     * Display the specified resource.
     */
    public function show(Kategori $kategori)
    {
        $kategori->load('bukus');
        return view('kategori.show', compact('kategori'));
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Kategori $kategori)
    {
        return view('kategori.edit', compact('kategori'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Kategori $kategori)
    {
        $validated = $request->validate([
            'kode_kategori' => ['required', 'string', 'max:10', Rule::unique('kategoris', 'kode_kategori')->ignore($kategori->id)],
            'nama_kategori' => 'required|string|min:3|max:100',
        ]);

        $kategori->update($validated);

        return redirect()->route('kategori.index')->with('success', 'Data kategori berhasil diperbarui!');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Kategori $kategori)
    {
        $nama = $kategori->nama_kategori;
        $kategori->delete();

        return redirect()->route('kategori.index')->with('success', "Kategori '{$nama}' berhasil dihapus!");
    }
}
