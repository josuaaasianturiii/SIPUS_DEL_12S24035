<x-layout>
    <x-slot:title>Detail Kategori — {{ $kategori->nama_kategori }}</x-slot:title>

    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <a href="{{ route('kategori.index') }}" class="btn btn-sm btn-outline-secondary mb-2">
                <i class="bi bi-arrow-left me-1"></i> Kembali ke Daftar Kategori
            </a>
            <h2 class="fw-bold text-del-purple mb-0">
                <i class="bi bi-tag-fill me-2"></i>{{ $kategori->nama_kategori }}
            </h2>
        </div>
        <div class="d-flex gap-2">
            <a href="{{ route('kategori.edit', $kategori) }}" class="btn btn-warning shadow-sm">
                <i class="bi bi-pencil-square me-1"></i> Ubah
            </a>
            <form action="{{ route('kategori.destroy', $kategori) }}" method="POST" onsubmit="return confirm('Apakah Anda yakin ingin menghapus kategori ini beserta seluruh buku di dalamnya?');">
                @csrf
                @method('DELETE')
                <button type="submit" class="btn btn-danger shadow-sm">
                    <i class="bi bi-trash me-1"></i> Hapus
                </button>
            </form>
        </div>
    </div>

    <!-- Info Box -->
    <div class="row mb-4">
        <div class="col-md-6 mb-3 mb-md-0">
            <div class="card card-custom p-3 border-start border-4 border-primary">
                <div class="d-flex align-items-center">
                    <div class="bg-del-purple p-3 rounded-3 me-3">
                        <i class="bi bi-upc-scan fs-3"></i>
                    </div>
                    <div>
                        <div class="text-muted small">Kode Kategori</div>
                        <h4 class="fw-bold mb-0 text-del-purple">{{ $kategori->kode_kategori }}</h4>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-6">
            <div class="card card-custom p-3 border-start border-4 border-success">
                <div class="d-flex align-items-center">
                    <div class="bg-success text-white p-3 rounded-3 me-3">
                        <i class="bi bi-journals fs-3"></i>
                    </div>
                    <div>
                        <div class="text-muted small">Total Koleksi Buku</div>
                        <h4 class="fw-bold mb-0 text-success">{{ $kategori->bukus->count() }} Judul Buku</h4>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Buku List under this Category -->
    <div class="card card-custom">
        <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center">
            <h5 class="fw-bold mb-0 text-dark">
                <i class="bi bi-book me-2 text-del-purple"></i>Daftar Buku dalam Kategori Ini
            </h5>
            <a href="{{ route('buku.create') }}" class="btn btn-sm btn-del">
                <i class="bi bi-plus-circle me-1"></i> Tambah Buku Baru
            </a>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th class="text-center py-3" style="width: 5%">#</th>
                            <th class="py-3" style="width: 15%">ISBN</th>
                            <th class="py-3" style="width: 35%">Judul & Penulis</th>
                            <th class="py-3" style="width: 20%">Penerbit / Tahun</th>
                            <th class="text-center py-3" style="width: 10%">Stok</th>
                            <th class="text-center py-3" style="width: 15%">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($kategori->bukus as $index => $buku)
                            <tr>
                                <td class="text-center fw-semibold text-muted">{{ $index + 1 }}</td>
                                <td>
                                    <code class="text-dark fw-semibold">{{ $buku->isbn }}</code>
                                </td>
                                <td>
                                    <div class="fw-bold text-dark">{{ $buku->judul }}</div>
                                    <div class="text-muted small"><i class="bi bi-person me-1"></i>{{ $buku->penulis }}</div>
                                </td>
                                <td>
                                    <div class="text-dark">{{ $buku->penerbit }}</div>
                                    <div class="text-muted small"><i class="bi bi-calendar3 me-1"></i>{{ $buku->tahun_terbit }}</div>
                                </td>
                                <td class="text-center">
                                    @if($buku->stok > 5)
                                        <span class="badge bg-success-subtle text-success border border-success-subtle px-2 py-1">
                                            {{ $buku->stok }} eks
                                        </span>
                                    @elseif($buku->stok > 0)
                                        <span class="badge bg-warning-subtle text-warning border border-warning-subtle px-2 py-1">
                                            {{ $buku->stok }} eks (Menipis)
                                        </span>
                                    @else
                                        <span class="badge bg-danger-subtle text-danger border border-danger-subtle px-2 py-1">
                                            Habis
                                        </span>
                                    @endif
                                </td>
                                <td class="text-center">
                                    <div class="btn-group btn-group-sm" role="group">
                                        <a href="{{ route('buku.show', $buku) }}" class="btn btn-outline-info" title="Detail">
                                            <i class="bi bi-eye"></i>
                                        </a>
                                        <a href="{{ route('buku.edit', $buku) }}" class="btn btn-outline-warning" title="Ubah">
                                            <i class="bi bi-pencil-square"></i>
                                        </a>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="text-center py-5 text-muted">
                                    <i class="bi bi-journal-x fs-1 d-block mb-2 text-secondary"></i>
                                    <p class="mb-0">Belum ada buku yang terdaftar dalam kategori ini.</p>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</x-layout>
