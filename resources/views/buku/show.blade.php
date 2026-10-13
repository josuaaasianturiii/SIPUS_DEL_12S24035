<x-layout>
    <x-slot:title>Detail Buku — {{ $buku->judul }}</x-slot:title>

    <div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-2">
        <div>
            <a href="{{ route('buku.index') }}" class="btn btn-sm btn-outline-secondary mb-2">
                <i class="bi bi-arrow-left me-1"></i> Kembali ke Katalog Buku
            </a>
            <h2 class="fw-bold text-del-purple mb-0">
                <i class="bi bi-book-half me-2"></i>Informasi Detail Buku
            </h2>
        </div>
        <div class="d-flex gap-2 flex-wrap">
            @if($buku->stok > 0)
                <a href="{{ route('peminjaman.create', ['buku_id' => $buku->id]) }}" class="btn btn-del shadow-sm">
                    <i class="bi bi-journal-arrow-down me-1"></i> Pinjam Buku Ini
                </a>
            @else
                <button class="btn btn-secondary shadow-sm" disabled title="Stok habis">
                    <i class="bi bi-x-circle me-1"></i> Stok Habis (Tidak Dapat Dipinjam)
                </button>
            @endif
            <a href="{{ route('buku.edit', $buku) }}" class="btn btn-warning shadow-sm">
                <i class="bi bi-pencil-square me-1"></i> Ubah Data
            </a>
            <form action="{{ route('buku.destroy', $buku) }}" method="POST" onsubmit="return confirm('Apakah Anda yakin ingin menghapus buku \'{{ $buku->judul }}\'?');">
                @csrf
                @method('DELETE')
                <button type="submit" class="btn btn-danger shadow-sm">
                    <i class="bi bi-trash me-1"></i> Hapus
                </button>
            </form>
        </div>
    </div>

    <div class="row">
        <!-- Main Book Information Card -->
        <div class="col-lg-8 mb-4">
            <div class="card card-custom h-100">
                <div class="card-header card-header-del py-3">
                    <h5 class="card-title mb-0 fw-bold">
                        <i class="bi bi-card-text me-2"></i>{{ $buku->judul }}
                    </h5>
                </div>
                <div class="card-body p-4">
                    <div class="row g-3 mb-4">
                        <div class="col-sm-6">
                            <span class="text-muted small d-block">Nomor ISBN:</span>
                            <span class="badge bg-light text-dark border font-monospace fs-6 px-2 py-1 mt-1">
                                {{ $buku->isbn }}
                            </span>
                        </div>
                        <div class="col-sm-6">
                            <span class="text-muted small d-block">Kategori:</span>
                            <a href="{{ route('kategori.show', $buku->kategori) }}" class="badge badge-del text-decoration-none fs-6 px-2 py-1 mt-1 d-inline-block">
                                <i class="bi bi-tag-fill me-1"></i>{{ $buku->kategori->nama_kategori }} ({{ $buku->kategori->kode_kategori }})
                            </a>
                        </div>
                        <div class="col-sm-6">
                            <span class="text-muted small d-block">Penulis / Pengarang:</span>
                            <span class="fw-semibold text-dark fs-6">{{ $buku->penulis }}</span>
                        </div>
                        <div class="col-sm-6">
                            <span class="text-muted small d-block">Penerbit:</span>
                            <span class="fw-semibold text-dark fs-6">{{ $buku->penerbit }}</span>
                        </div>
                        <div class="col-sm-6">
                            <span class="text-muted small d-block">Tahun Terbit:</span>
                            <span class="fw-semibold text-dark fs-6">{{ $buku->tahun_terbit }}</span>
                        </div>
                        <div class="col-sm-6">
                            <span class="text-muted small d-block">Status Ketersediaan Stok:</span>
                            @if($buku->stok > 5)
                                <span class="badge bg-success-subtle text-success border border-success-subtle px-3 py-2 mt-1 fs-6">
                                    <i class="bi bi-check-circle me-1"></i>Tersedia ({{ $buku->stok }} Eksemplar)
                                </span>
                            @elseif($buku->stok > 0)
                                <span class="badge bg-warning-subtle text-warning-emphasis border border-warning-subtle px-3 py-2 mt-1 fs-6">
                                    <i class="bi bi-exclamation-circle me-1"></i>Stok Menipis ({{ $buku->stok }} Eksemplar)
                                </span>
                            @else
                                <span class="badge bg-danger-subtle text-danger border border-danger-subtle px-3 py-2 mt-1 fs-6">
                                    <i class="bi bi-x-circle me-1"></i>Stok Habis
                                </span>
                            @endif
                        </div>
                    </div>

                    <hr>

                    <div>
                        <h6 class="fw-bold text-dark mb-2">
                            <i class="bi bi-text-paragraph me-1 text-del-purple"></i>Sinopsis / Ringkasan Buku:
                        </h6>
                        <div class="p-3 bg-light rounded-3 text-secondary lh-lg mb-4">
                            @if($buku->sinopsis)
                                {{ $buku->sinopsis }}
                            @else
                                <em class="text-muted">Tidak ada deskripsi sinopsis untuk buku ini.</em>
                            @endif
                        </div>
                    </div>

                    <div>
                        <h6 class="fw-bold text-dark mb-2">
                            <i class="bi bi-clock-history me-1 text-del-purple"></i>Riwayat Sirkulasi Peminjaman Buku Ini:
                        </h6>
                        @if($buku->peminjamans->count() > 0)
                            <div class="table-responsive">
                                <table class="table table-sm table-bordered align-middle small mb-0">
                                    <thead class="table-light">
                                        <tr>
                                            <th>Kode Pinjam</th>
                                            <th>NIM & Nama</th>
                                            <th>Tgl Pinjam</th>
                                            <th>Jatuh Tempo</th>
                                            <th>Status</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach($buku->peminjamans->take(5) as $p)
                                            <tr>
                                                <td class="font-monospace fw-semibold">{{ $p->kode_pinjam }}</td>
                                                <td>{{ $p->nim_peminjam }} - {{ $p->nama_peminjam }}</td>
                                                <td>{{ \Carbon\Carbon::parse($p->tanggal_pinjam)->format('d/m/Y') }}</td>
                                                <td>{{ \Carbon\Carbon::parse($p->tanggal_jatuh_tempo)->format('d/m/Y') }}</td>
                                                <td>
                                                    @if($p->status === 'dipinjam')
                                                        <span class="badge bg-warning text-dark">Dipinjam</span>
                                                    @else
                                                        <span class="badge bg-success">Dikembalikan</span>
                                                    @endif
                                                </td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        @else
                            <div class="p-3 bg-light rounded text-muted small">
                                <i class="bi bi-info-circle me-1"></i>Belum ada riwayat peminjaman untuk buku ini.
                            </div>
                        @endif
                    </div>
                </div>
            </div>
        </div>

        <!-- Sidebar / Metadata Card -->
        <div class="col-lg-4 mb-4">
            <div class="card card-custom mb-3">
                <div class="card-header bg-white py-3">
                    <h6 class="fw-bold mb-0 text-dark">
                        <i class="bi bi-info-circle me-1 text-del-purple"></i>Informasi Sistem
                    </h6>
                </div>
                <div class="card-body">
                    <ul class="list-group list-group-flush small">
                        <li class="list-group-item d-flex justify-content-between px-0">
                            <span class="text-muted">ID Database:</span>
                            <span class="fw-bold">#{{ $buku->id }}</span>
                        </li>
                        <li class="list-group-item d-flex justify-content-between px-0">
                            <span class="text-muted">Didaftarkan pada:</span>
                            <span>{{ $buku->created_at ? $buku->created_at->translatedFormat('d F Y, H:i') : '-' }}</span>
                        </li>
                        <li class="list-group-item d-flex justify-content-between px-0">
                            <span class="text-muted">Terakhir diperbarui:</span>
                            <span>{{ $buku->updated_at ? $buku->updated_at->translatedFormat('d F Y, H:i') : '-' }}</span>
                        </li>
                    </ul>
                </div>
            </div>

            <div class="card card-custom bg-del-purple text-white p-3">
                <div class="d-flex align-items-center gap-3">
                    <i class="bi bi-shield-check fs-1"></i>
                    <div>
                        <h6 class="fw-bold mb-1">Koleksi Terverifikasi</h6>
                        <small class="opacity-75">Tercatat resmi dalam sistem repositori perpustakaan IT Del.</small>
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-layout>
