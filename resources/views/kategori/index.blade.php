<x-layout>
    <x-slot:title>Daftar Kategori Buku</x-slot:title>

    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h2 class="fw-bold text-del-purple mb-1">
                <i class="bi bi-tags-fill me-2"></i>Daftar Kategori Buku
            </h2>
            <p class="text-muted mb-0">Kelola kategori dan klasifikasi buku di perpustakaan SIPUS-Del.</p>
        </div>
        <a href="{{ route('kategori.create') }}" class="btn btn-del shadow-sm">
            <i class="bi bi-plus-lg me-1"></i> Tambah Kategori
        </a>
    </div>

    <div class="card card-custom">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th class="text-center py-3" style="width: 5%">#</th>
                            <th class="py-3" style="width: 20%">Kode Kategori</th>
                            <th class="py-3" style="width: 40%">Nama Kategori</th>
                            <th class="text-center py-3" style="width: 15%">Jumlah Buku</th>
                            <th class="text-center py-3" style="width: 20%">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($kategoris as $index => $kategori)
                            <tr>
                                <td class="text-center fw-semibold text-muted">
                                    {{ $kategoris->firstItem() + $index }}
                                </td>
                                <td>
                                    <span class="badge badge-del px-2 py-1 fs-6">
                                        {{ $kategori->kode_kategori }}
                                    </span>
                                </td>
                                <td class="fw-semibold text-dark">
                                    {{ $kategori->nama_kategori }}
                                </td>
                                <td class="text-center">
                                    <span class="badge rounded-pill bg-secondary px-3 py-2">
                                        <i class="bi bi-book me-1"></i>{{ $kategori->bukus_count }} Judul
                                    </span>
                                </td>
                                <td class="text-center">
                                    <div class="btn-group btn-group-sm" role="group">
                                        <a href="{{ route('kategori.show', $kategori) }}" class="btn btn-outline-info" title="Detail Kategori">
                                            <i class="bi bi-eye"></i>
                                        </a>
                                        <a href="{{ route('kategori.edit', $kategori) }}" class="btn btn-outline-warning" title="Ubah Kategori">
                                            <i class="bi bi-pencil-square"></i>
                                        </a>
                                        <form action="{{ route('kategori.destroy', $kategori) }}" method="POST" class="d-inline" onsubmit="return confirm('Apakah Anda yakin ingin menghapus kategori \'{{ $kategori->nama_kategori }}\'? Semua buku dalam kategori ini juga akan terhapus!');">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="btn btn-outline-danger" title="Hapus Kategori">
                                                <i class="bi bi-trash"></i>
                                            </button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="text-center py-5 text-muted">
                                    <i class="bi bi-inbox fs-1 d-block mb-2 text-secondary"></i>
                                    <p class="mb-2 fw-medium">Belum ada data kategori yang tersimpan.</p>
                                    <a href="{{ route('kategori.create') }}" class="btn btn-sm btn-del">
                                        <i class="bi bi-plus-circle me-1"></i> Tambah Kategori Pertama
                                    </a>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
        @if($kategoris->hasPages())
            <div class="card-footer bg-white py-3">
                <div class="d-flex justify-content-between align-items-center flex-wrap gap-2">
                    <small class="text-muted">
                        Menampilkan data <strong>{{ $kategoris->firstItem() }}</strong> sampai <strong>{{ $kategoris->lastItem() }}</strong> dari <strong>{{ $kategoris->total() }}</strong> total kategori
                    </small>
                    <div>
                        {{ $kategoris->links() }}
                    </div>
                </div>
            </div>
        @endif
    </div>
</x-layout>
