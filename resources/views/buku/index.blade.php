<x-layout>
    <x-slot:title>Daftar Koleksi Buku</x-slot:title>

    <div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-2">
        <div>
            <h2 class="fw-bold text-del-purple mb-1">
                <i class="bi bi-book-half me-2"></i>Koleksi Buku & Dasbor Perpustakaan
            </h2>
            <p class="text-muted mb-0">Manajemen katalog buku, rekapitulasi analitik Query Builder, dan sirkulasi peminjaman IT Del.</p>
        </div>
        <div class="d-flex gap-2">
            <a href="{{ route('peminjaman.create') }}" class="btn btn-del shadow-sm">
                <i class="bi bi-journal-arrow-down me-1"></i> Pinjam Buku
            </a>
            <a href="{{ route('buku.create') }}" class="btn btn-outline-del shadow-sm">
                <i class="bi bi-plus-lg me-1"></i> Tambah Buku Baru
            </a>
        </div>
    </div>

    <!-- Kartu Statistik Perpustakaan (Fluent Query Builder DB::table) -->
    <div class="row g-3 mb-4">
        <div class="col-sm-6 col-xl-3">
            <div class="card card-custom border-0 shadow-sm h-100 bg-white">
                <div class="card-body p-3 d-flex align-items-center gap-3">
                    <div class="rounded-3 bg-del-purple text-white p-3 d-flex align-items-center justify-content-center" style="width: 52px; height: 52px;">
                        <i class="bi bi-journal-bookmark-fill fs-4"></i>
                    </div>
                    <div>
                        <div class="text-muted small fw-medium">Total Judul Buku</div>
                        <div class="fs-4 fw-bold text-dark">{{ number_format($stats['total_judul'] ?? 0) }}</div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-sm-6 col-xl-3">
            <div class="card card-custom border-0 shadow-sm h-100 bg-white">
                <div class="card-body p-3 d-flex align-items-center gap-3">
                    <div class="rounded-3 bg-success text-white p-3 d-flex align-items-center justify-content-center" style="width: 52px; height: 52px;">
                        <i class="bi bi-box-seam-fill fs-4"></i>
                    </div>
                    <div>
                        <div class="text-muted small fw-medium">Total Stok Tersedia</div>
                        <div class="fs-4 fw-bold text-success">{{ number_format($stats['total_stok'] ?? 0) }} <span class="fs-6 fw-normal text-muted">eks</span></div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-sm-6 col-xl-3">
            <div class="card card-custom border-0 shadow-sm h-100 bg-white">
                <div class="card-body p-3 d-flex align-items-center gap-3">
                    <div class="rounded-3 bg-warning text-dark p-3 d-flex align-items-center justify-content-center" style="width: 52px; height: 52px;">
                        <i class="bi bi-hourglass-split fs-4"></i>
                    </div>
                    <div>
                        <div class="text-muted small fw-medium">Sedang Dipinjam</div>
                        <div class="fs-4 fw-bold text-warning-emphasis">{{ number_format($stats['total_dipinjam'] ?? 0) }} <span class="fs-6 fw-normal text-muted">transaksi</span></div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-sm-6 col-xl-3">
            <div class="card card-custom border-0 shadow-sm h-100 bg-white">
                <div class="card-body p-3 d-flex align-items-center gap-3">
                    <div class="rounded-3 bg-info text-white p-3 d-flex align-items-center justify-content-center" style="width: 52px; height: 52px;">
                        <i class="bi bi-check-circle-fill fs-4"></i>
                    </div>
                    <div>
                        <div class="text-muted small fw-medium">Total Dikembalikan</div>
                        <div class="fs-4 fw-bold text-info-emphasis">{{ number_format($stats['total_dikembalikan'] ?? 0) }} <span class="fs-6 fw-normal text-muted">transaksi</span></div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Filter & Pencarian Dinamis -->
    <div class="card card-custom mb-4 border-0 shadow-sm">
        <div class="card-body p-3">
            <form action="{{ route('buku.index') }}" method="GET" class="row g-2 align-items-center">
                <div class="col-md-3">
                    <select name="kategori_id" class="form-select" onchange="this.form.submit()">
                        <option value="">-- Semua Kategori --</option>
                        @foreach($kategoris as $kat)
                            <option value="{{ $kat->id }}" {{ request('kategori_id') == $kat->id ? 'selected' : '' }}>
                                {{ $kat->nama_kategori }} ({{ $kat->kode_kategori }})
                            </option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-7">
                    <div class="input-group">
                        <span class="input-group-text bg-white"><i class="bi bi-search"></i></span>
                        <input type="text" name="search" class="form-control" placeholder="Cari berdasarkan Judul, Penulis, atau ISBN..." value="{{ request('search') }}">
                    </div>
                </div>
                <div class="col-md-2 d-flex gap-2">
                    <button type="submit" class="btn btn-del w-100">Cari</button>
                    @if(request()->hasAny(['search', 'kategori_id']))
                        <a href="{{ route('buku.index') }}" class="btn btn-outline-secondary" title="Reset Filter"><i class="bi bi-arrow-counterclockwise"></i></a>
                    @endif
                </div>
            </form>
            @if(session('last_buku_search'))
                <div class="mt-2 small text-muted">
                    <i class="bi bi-clock-history me-1"></i>Pencarian terakhir di sesi Anda: <span class="badge bg-light text-dark border">{{ session('last_buku_search') }}</span>
                </div>
            @endif
        </div>
    </div>

    <!-- Tabel Buku dengan withCount Peminjaman -->
    <div class="card card-custom border-0 shadow-sm">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th class="text-center py-3" style="width: 5%">#</th>
                            <th class="py-3" style="width: 15%">ISBN</th>
                            <th class="py-3" style="width: 27%">Judul & Penulis</th>
                            <th class="py-3" style="width: 15%">Kategori</th>
                            <th class="text-center py-3" style="width: 10%">Stok</th>
                            <th class="text-center py-3" style="width: 12%">Sirkulasi</th>
                            <th class="text-center py-3" style="width: 16%">Aksi</th>
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
                                    <a href="{{ route('buku.show', $buku) }}" class="fw-bold text-dark text-decoration-none d-block">
                                        {{ $buku->judul }}
                                    </a>
                                    <span class="text-muted small">
                                        <i class="bi bi-pen me-1"></i>{{ $buku->penulis }} &bull; {{ $buku->penerbit }} ({{ $buku->tahun_terbit }})
                                    </span>
                                </td>
                                <td>
                                    <a href="{{ route('kategori.show', $buku->kategori) }}" class="badge badge-del text-decoration-none px-2 py-1">
                                        <i class="bi bi-tag-fill me-1"></i>{{ $buku->kategori->nama_kategori }}
                                    </a>
                                </td>
                                <td class="text-center">
                                    @if($buku->stok > 5)
                                        <span class="badge bg-success-subtle text-success border border-success-subtle px-2 py-1">
                                            {{ $buku->stok }} eks
                                        </span>
                                    @elseif($buku->stok > 0)
                                        <span class="badge bg-warning-subtle text-warning-emphasis border border-warning-subtle px-2 py-1">
                                            {{ $buku->stok }} eks
                                        </span>
                                    @else
                                        <span class="badge bg-danger-subtle text-danger border border-danger-subtle px-2 py-1">
                                            Habis
                                        </span>
                                    @endif
                                </td>
                                <td class="text-center">
                                    <span class="badge bg-secondary-subtle text-secondary border font-monospace px-2 py-1" title="Jumlah total transaksi peminjaman buku ini">
                                        <i class="bi bi-arrow-left-right me-1"></i>{{ $buku->peminjamans_count }}x dipinjam
                                    </span>
                                </td>
                                <td class="text-center">
                                    <div class="btn-group btn-group-sm" role="group">
                                        @if($buku->stok > 0)
                                            <a href="{{ route('peminjaman.create', ['buku_id' => $buku->id]) }}" class="btn btn-outline-success" title="Pinjam Buku">
                                                <i class="bi bi-journal-arrow-down"></i>
                                            </a>
                                        @else
                                            <button class="btn btn-outline-secondary" disabled title="Stok habis">
                                                <i class="bi bi-x-circle"></i>
                                            </button>
                                        @endif
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
                                    <p class="mb-2 fw-medium">Tidak ada buku yang sesuai dengan kriteria filter/pencarian.</p>
                                    <a href="{{ route('buku.index') }}" class="btn btn-sm btn-outline-del">
                                        <i class="bi bi-arrow-counterclockwise me-1"></i> Reset Pencarian
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
