<x-layout>
    <x-slot:title>Sirkulasi Peminjaman Buku</x-slot:title>

    <div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-2">
        <div>
            <h2 class="fw-bold text-del-purple mb-1">
                <i class="bi bi-arrow-left-right me-2"></i>Sirkulasi Peminjaman & Pengembalian
            </h2>
            <p class="text-muted mb-0">Catatan peminjaman aktif, pengembalian buku, dan riwayat transaksi perpustakaan.</p>
        </div>
        <div>
            <a href="{{ route('peminjaman.create') }}" class="btn btn-del shadow-sm">
                <i class="bi bi-plus-circle-fill me-1"></i> Catat Peminjaman Baru
            </a>
        </div>
    </div>

    <!-- Filter & Pencarian -->
    <div class="card card-custom mb-4">
        <div class="card-body p-3">
            <form action="{{ route('peminjaman.index') }}" method="GET" class="row g-2 align-items-center">
                <div class="col-md-3">
                    <select name="status" class="form-select" onchange="this.form.submit()">
                        <option value="">-- Semua Status --</option>
                        <option value="dipinjam" {{ request('status') === 'dipinjam' ? 'selected' : '' }}>Sedang Dipinjam</option>
                        <option value="dikembalikan" {{ request('status') === 'dikembalikan' ? 'selected' : '' }}>Sudah Dikembalikan</option>
                    </select>
                </div>
                <div class="col-md-7">
                    <div class="input-group">
                        <span class="input-group-text bg-white"><i class="bi bi-search"></i></span>
                        <input type="text" name="search" class="form-control" placeholder="Cari Kode Pinjam, NIM, Nama Mahasiswa, atau Judul Buku..." value="{{ request('search') }}">
                    </div>
                </div>
                <div class="col-md-2 d-flex gap-2">
                    <button type="submit" class="btn btn-del w-100">Filter</button>
                    @if(request()->hasAny(['status', 'search']))
                        <a href="{{ route('peminjaman.index') }}" class="btn btn-outline-secondary" title="Reset Filter"><i class="bi bi-arrow-counterclockwise"></i></a>
                    @endif
                </div>
            </form>
        </div>
    </div>

    <!-- Tabel Peminjaman -->
    <div class="card card-custom">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th class="text-center py-3" style="width: 5%">#</th>
                            <th class="py-3" style="width: 15%">Kode Pinjam</th>
                            <th class="py-3" style="width: 25%">Buku</th>
                            <th class="py-3" style="width: 20%">Peminjam (NIM - Nama)</th>
                            <th class="py-3" style="width: 15%">Periode Pinjam</th>
                            <th class="text-center py-3" style="width: 10%">Status</th>
                            <th class="text-center py-3" style="width: 10%">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($peminjamans as $index => $pinjam)
                            <tr>
                                <td class="text-center fw-semibold text-muted">
                                    {{ $peminjamans->firstItem() + $index }}
                                </td>
                                <td>
                                    <span class="badge bg-light text-dark border font-monospace px-2 py-1">
                                        {{ $pinjam->kode_pinjam }}
                                    </span>
                                </td>
                                <td>
                                    <a href="{{ route('buku.show', $pinjam->buku) }}" class="fw-bold text-dark text-decoration-none d-block">
                                        {{ $pinjam->buku->judul }}
                                    </a>
                                    <span class="text-muted small">ISBN: {{ $pinjam->buku->isbn }}</span>
                                </td>
                                <td>
                                    <div class="fw-semibold text-dark">{{ $pinjam->nama_peminjam }}</div>
                                    <span class="badge bg-secondary-subtle text-secondary border font-monospace">
                                        {{ $pinjam->nim_peminjam }}
                                    </span>
                                </td>
                                <td>
                                    <div class="small text-muted">Pinjam: <strong>{{ \Carbon\Carbon::parse($pinjam->tanggal_pinjam)->format('d M Y') }}</strong></div>
                                    <div class="small text-danger">Tempo: <strong>{{ \Carbon\Carbon::parse($pinjam->tanggal_jatuh_tempo)->format('d M Y') }}</strong></div>
                                    @if($pinjam->tanggal_kembali)
                                        <div class="small text-success">Kembali: <strong>{{ \Carbon\Carbon::parse($pinjam->tanggal_kembali)->format('d M Y') }}</strong></div>
                                    @endif
                                </td>
                                <td class="text-center">
                                    @if($pinjam->status === 'dipinjam')
                                        <span class="badge bg-warning text-dark px-2 py-1">
                                            <i class="bi bi-hourglass-split me-1"></i>Dipinjam
                                        </span>
                                    @else
                                        <span class="badge bg-success px-2 py-1">
                                            <i class="bi bi-check-circle-fill me-1"></i>Dikembalikan
                                        </span>
                                    @endif
                                </td>
                                <td class="text-center">
                                    @if($pinjam->status === 'dipinjam')
                                        <form action="{{ route('peminjaman.kembalikan', $pinjam->id) }}" method="POST" onsubmit="return confirm('Konfirmasi pengembalian buku \'{{ $pinjam->buku->judul }}\' oleh NIM {{ $pinjam->nim_peminjam }}?');">
                                            @csrf
                                            <button type="submit" class="btn btn-sm btn-success shadow-sm" title="Kembalikan Buku">
                                                <i class="bi bi-box-arrow-in-down me-1"></i> Kembali
                                            </button>
                                        </form>
                                    @else
                                        <span class="text-muted small"><i class="bi bi-check-all"></i> Selesai</span>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="text-center py-5 text-muted">
                                    <i class="bi bi-inbox fs-1 d-block mb-2 text-secondary"></i>
                                    <p class="mb-2 fw-medium">Belum ada riwayat transaksi peminjaman buku.</p>
                                    <a href="{{ route('peminjaman.create') }}" class="btn btn-sm btn-del">
                                        <i class="bi bi-plus-circle me-1"></i> Catat Peminjaman Pertama
                                    </a>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
        @if($peminjamans->hasPages())
            <div class="card-footer bg-white py-3">
                <div class="d-flex justify-content-between align-items-center flex-wrap gap-2">
                    <small class="text-muted">
                        Menampilkan <strong>{{ $peminjamans->firstItem() }}</strong> sampai <strong>{{ $peminjamans->lastItem() }}</strong> dari <strong>{{ $peminjamans->total() }}</strong> total transaksi
                    </small>
                    <div>
                        {{ $peminjamans->links() }}
                    </div>
                </div>
            </div>
        @endif
    </div>
</x-layout>
