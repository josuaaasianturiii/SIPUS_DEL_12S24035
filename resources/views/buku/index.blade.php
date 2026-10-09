<x-layout>
    <x-slot:title>Daftar Koleksi Buku</x-slot:title>

    <div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-2">
        <div>
            <h2 class="fw-bold text-del-purple mb-1">
                <i class="bi bi-book-half me-2"></i>Koleksi Buku Perpustakaan
            </h2>
            <p class="text-muted mb-0">Manajemen katalog buku, inventaris stok, dan kategori perpustakaan Del.</p>
        </div>
        <div class="d-flex gap-2">
            <a href="{{ route('buku.create') }}" class="btn btn-del shadow-sm">
                <i class="bi bi-plus-lg me-1"></i> Tambah Buku Baru
            </a>
            <a href="{{ route('kategori.index') }}" class="btn btn-outline-del shadow-sm">
                <i class="bi bi-tags me-1"></i> Kelola Kategori
            </a>
        </div>
    </div>

    <div class="card card-custom">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th class="text-center py-3" style="width: 5%">#</th>
                            <th class="py-3" style="width: 15%">ISBN</th>
                            <th class="py-3" style="width: 30%">Judul & Penulis</th>
                            <th class="py-3" style="width: 15%">Kategori</th>
                            <th class="py-3" style="width: 15%">Penerbit & Tahun</th>
                            <th class="text-center py-3" style="width: 8%">Stok</th>
                            <th class="text-center py-3" style="width: 12%">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($bukus as $index => $buku)
                            <tr>
                                <td class="text-center fw-semibold text-muted">
                                    {{ $bukus->firstItem() + $index }}
                                </td>
                                <td>
                                    <span class="badge bg-light text-dark border font-monospace px-2 py-1">
                                        {{ $buku->isbn }}
                                    </span>
                                </td>
                                <td>
                                    <a href="{{ route('buku.show', $buku) }}" class="fw-bold text-dark text-decoration-none hover-primary d-block">
                                        {{ $buku->judul }}
                                    </a>
                                    <span class="text-muted small">
                                        <i class="bi bi-pen me-1"></i>{{ $buku->penulis }}
                                    </span>
                                </td>
                                <td>
                                    <a href="{{ route('kategori.show', $buku->kategori) }}" class="badge badge-del text-decoration-none px-2 py-1">
                                        <i class="bi bi-tag-fill me-1"></i>{{ $buku->kategori->nama_kategori }}
                                    </a>
                                </td>
                                <td>
                                    <div class="text-dark small fw-medium">{{ $buku->penerbit }}</div>
                                    <div class="text-muted small">Tahun: {{ $buku->tahun_terbit }}</div>
                                </td>
                                <td class="text-center">
                                    @if($buku->stok > 5)
                                        <span class="badge bg-success-subtle text-success border border-success-subtle px-2 py-1">
                                            {{ $buku->stok }}
                                        </span>
                                    @elseif($buku->stok > 0)
                                        <span class="badge bg-warning-subtle text-warning-emphasis border border-warning-subtle px-2 py-1">
                                            {{ $buku->stok }}
                                        </span>
                                    @else
                                        <span class="badge bg-danger-subtle text-danger border border-danger-subtle px-2 py-1">
                                            Habis
                                        </span>
                                    @endif
                                </td>
                                <td class="text-center">
                                    <div class="btn-group btn-group-sm" role="group">
                                        <a href="{{ route('buku.show', $buku) }}" class="btn btn-outline-info" title="Lihat Detail">
                                            <i class="bi bi-eye"></i>
                                        </a>
                                        <a href="{{ route('buku.edit', $buku) }}" class="btn btn-outline-warning" title="Ubah Data">
                                            <i class="bi bi-pencil-square"></i>
                                        </a>
                                        <form action="{{ route('buku.destroy', $buku) }}" method="POST" class="d-inline" onsubmit="return confirm('Apakah Anda yakin ingin menghapus buku \'{{ $buku->judul }}\'?');">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="btn btn-outline-danger" title="Hapus Buku">
                                                <i class="bi bi-trash"></i>
                                            </button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="text-center py-5 text-muted">
                                    <i class="bi bi-journal-x fs-1 d-block mb-2 text-secondary"></i>
                                    <p class="mb-2 fw-medium">Belum ada data buku dalam katalog perpustakaan.</p>
                                    <a href="{{ route('buku.create') }}" class="btn btn-sm btn-del">
                                        <i class="bi bi-plus-circle me-1"></i> Tambah Buku Pertama
                                    </a>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
        @if($bukus->hasPages())
            <div class="card-footer bg-white py-3">
                <div class="d-flex justify-content-between align-items-center flex-wrap gap-2">
                    <small class="text-muted">
                        Menampilkan data <strong>{{ $bukus->firstItem() }}</strong> sampai <strong>{{ $bukus->lastItem() }}</strong> dari <strong>{{ $bukus->total() }}</strong> total buku
                    </small>
                    <div>
                        {{ $bukus->links() }}
                    </div>
                </div>
            </div>
        @endif
    </div>
</x-layout>
