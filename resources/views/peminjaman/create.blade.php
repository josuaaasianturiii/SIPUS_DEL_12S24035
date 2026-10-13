<x-layout>
    <x-slot:title>Catat Peminjaman Buku</x-slot:title>

    <div class="row justify-content-center">
        <div class="col-lg-8">
            <div class="card card-custom shadow-sm border-0">
                <div class="card-header bg-del-purple py-3 text-white">
                    <h5 class="card-title mb-0 fw-bold">
                        <i class="bi bi-journal-arrow-down me-2"></i>Form Transaksi Peminjaman Buku
                    </h5>
                    <small class="text-white-50">Transaksi atomik terlindungi locking baris (lockForUpdate) anti race-condition stok.</small>
                </div>
                <div class="card-body p-4">
                    <form action="{{ route('peminjaman.store') }}" method="POST">
                        @csrf

                        <!-- Pilihan Buku -->
                        <div class="mb-3">
                            <label for="buku_id" class="form-label fw-semibold">Pilih Judul Buku <span class="text-danger">*</span></label>
                            <select name="buku_id" id="buku_id" class="form-select @error('buku_id') is-invalid @enderror" required>
                                <option value="">-- Pilih Buku yang Tersedia --</option>
                                @foreach($bukus as $buku)
                                    <option value="{{ $buku->id }}" 
                                        {{ (old('buku_id', $selectedBukuId) == $buku->id) ? 'selected' : '' }}>
                                        {{ $buku->judul }} — [Stok: {{ $buku->stok }}] (ISBN: {{ $buku->isbn }})
                                    </option>
                                @endforeach
                            </select>
                            @error('buku_id')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                            <div class="form-text">Hanya buku dengan stok > 0 yang dapat dipinjam.</div>
                        </div>

                        <div class="row">
                            <!-- NIM Peminjam -->
                            <div class="col-md-5 mb-3">
                                <label for="nim_peminjam" class="form-label fw-semibold">NIM Mahasiswa <span class="text-danger">*</span></label>
                                <input type="text" name="nim_peminjam" id="nim_peminjam" 
                                    class="form-control text-uppercase font-monospace @error('nim_peminjam') is-invalid @enderror" 
                                    placeholder="Contoh: 12S24035" 
                                    value="{{ old('nim_peminjam', $lastNim) }}" required>
                                @error('nim_peminjam')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <!-- Nama Mahasiswa -->
                            <div class="col-md-7 mb-3">
                                <label for="nama_peminjam" class="form-label fw-semibold">Nama Mahasiswa <span class="text-danger">*</span></label>
                                <input type="text" name="nama_peminjam" id="nama_peminjam" 
                                    class="form-control @error('nama_peminjam') is-invalid @enderror" 
                                    placeholder="Nama Lengkap Mahasiswa" 
                                    value="{{ old('nama_peminjam', $lastNama) }}" required>
                                @error('nama_peminjam')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>

                        <div class="row">
                            <!-- Tanggal Pinjam -->
                            <div class="col-md-6 mb-3">
                                <label for="tanggal_pinjam" class="form-label fw-semibold">Tanggal Pinjam <span class="text-danger">*</span></label>
                                <input type="date" name="tanggal_pinjam" id="tanggal_pinjam" 
                                    class="form-control @error('tanggal_pinjam') is-invalid @enderror" 
                                    value="{{ old('tanggal_pinjam', now()->toDateString()) }}" required>
                                @error('tanggal_pinjam')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <!-- Tanggal Jatuh Tempo -->
                            <div class="col-md-6 mb-3">
                                <label for="tanggal_jatuh_tempo" class="form-label fw-semibold">Tanggal Jatuh Tempo <span class="text-danger">*</span></label>
                                <input type="date" name="tanggal_jatuh_tempo" id="tanggal_jatuh_tempo" 
                                    class="form-control @error('tanggal_jatuh_tempo') is-invalid @enderror" 
                                    value="{{ old('tanggal_jatuh_tempo', now()->addDays(7)->toDateString()) }}" required>
                                @error('tanggal_jatuh_tempo')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                                <div class="form-text">Standar durasi peminjaman: 7 hari.</div>
                            </div>
                        </div>

                        <hr class="my-4">

                        <div class="d-flex justify-content-between align-items-center">
                            <a href="{{ route('peminjaman.index') }}" class="btn btn-outline-secondary">
                                <i class="bi bi-arrow-left me-1"></i> Batal / Kembali
                            </a>
                            <button type="submit" class="btn btn-del px-4">
                                <i class="bi bi-shield-check me-1"></i> Simpan Transaksi Peminjaman (ACID)
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</x-layout>
