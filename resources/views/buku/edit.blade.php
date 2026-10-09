<x-layout>
    <x-slot:title>Ubah Data Buku — {{ $buku->judul }}</x-slot:title>

    <div class="row justify-content-center">
        <div class="col-lg-9">
            <div class="card card-custom">
                <div class="card-header card-header-del py-3">
                    <h5 class="card-title mb-0 fw-bold">
                        <i class="bi bi-pencil-square me-2"></i>Ubah Data Buku
                    </h5>
                </div>
                <div class="card-body p-4">
                    <form action="{{ route('buku.update', $buku) }}" method="POST">
                        @csrf
                        @method('PUT')

                        <div class="row g-3">
                            <!-- ISBN -->
                            <div class="col-md-6">
                                <label for="isbn" class="form-label fw-semibold">
                                    ISBN <span class="text-danger">*</span>
                                </label>
                                <input type="text" 
                                       name="isbn" 
                                       id="isbn" 
                                       class="form-control @error('isbn') is-invalid @enderror" 
                                       value="{{ old('isbn', $buku->isbn) }}" 
                                       required>
                                @error('isbn')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @else
                                    <div class="form-text">Kode ISBN unik buku (minimal 5 karakter).</div>
                                @enderror
                            </div>

                            <!-- Kategori ID -->
                            <div class="col-md-6">
                                <label for="kategori_id" class="form-label fw-semibold">
                                    Kategori Buku <span class="text-danger">*</span>
                                </label>
                                <select name="kategori_id" 
                                        id="kategori_id" 
                                        class="form-select @error('kategori_id') is-invalid @enderror" 
                                        required>
                                    @foreach($kategoris as $kategori)
                                        <option value="{{ $kategori->id }}" {{ old('kategori_id', $buku->kategori_id) == $kategori->id ? 'selected' : '' }}>
                                            [{{ $kategori->kode_kategori }}] {{ $kategori->nama_kategori }}
                                        </option>
                                    @endforeach
                                </select>
                                @error('kategori_id')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <!-- Judul Buku -->
                            <div class="col-12">
                                <label for="judul" class="form-label fw-semibold">
                                    Judul Buku <span class="text-danger">*</span>
                                </label>
                                <input type="text" 
                                       name="judul" 
                                       id="judul" 
                                       class="form-control @error('judul') is-invalid @enderror" 
                                       value="{{ old('judul', $buku->judul) }}" 
                                       required>
                                @error('judul')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <!-- Penulis -->
                            <div class="col-md-6">
                                <label for="penulis" class="form-label fw-semibold">
                                    Penulis / Pengarang <span class="text-danger">*</span>
                                </label>
                                <input type="text" 
                                       name="penulis" 
                                       id="penulis" 
                                       class="form-control @error('penulis') is-invalid @enderror" 
                                       value="{{ old('penulis', $buku->penulis) }}" 
                                       required>
                                @error('penulis')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <!-- Penerbit -->
                            <div class="col-md-6">
                                <label for="penerbit" class="form-label fw-semibold">
                                    Penerbit <span class="text-danger">*</span>
                                </label>
                                <input type="text" 
                                       name="penerbit" 
                                       id="penerbit" 
                                       class="form-control @error('penerbit') is-invalid @enderror" 
                                       value="{{ old('penerbit', $buku->penerbit) }}" 
                                       required>
                                @error('penerbit')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <!-- Tahun Terbit -->
                            <div class="col-md-6">
                                <label for="tahun_terbit" class="form-label fw-semibold">
                                    Tahun Terbit <span class="text-danger">*</span>
                                </label>
                                <input type="number" 
                                       name="tahun_terbit" 
                                       id="tahun_terbit" 
                                       class="form-control @error('tahun_terbit') is-invalid @enderror" 
                                       value="{{ old('tahun_terbit', $buku->tahun_terbit) }}" 
                                       min="1900" 
                                       max="{{ date('Y') + 1 }}" 
                                       required>
                                @error('tahun_terbit')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <!-- Stok -->
                            <div class="col-md-6">
                                <label for="stok" class="form-label fw-semibold">
                                    Jumlah Stok Eksemplar <span class="text-danger">*</span>
                                </label>
                                <input type="number" 
                                       name="stok" 
                                       id="stok" 
                                       class="form-control @error('stok') is-invalid @enderror" 
                                       value="{{ old('stok', $buku->stok) }}" 
                                       min="0" 
                                       required>
                                @error('stok')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <!-- Sinopsis -->
                            <div class="col-12">
                                <label for="sinopsis" class="form-label fw-semibold">
                                    Sinopsis / Deskripsi Ringkas
                                </label>
                                <textarea name="sinopsis" 
                                          id="sinopsis" 
                                          rows="4" 
                                          class="form-control @error('sinopsis') is-invalid @enderror">{{ old('sinopsis', $buku->sinopsis) }}</textarea>
                                @error('sinopsis')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>

                        <div class="d-flex justify-content-between align-items-center pt-4 border-top mt-4">
                            <a href="{{ route('buku.index') }}" class="btn btn-outline-secondary">
                                <i class="bi bi-arrow-left me-1"></i> Batal
                            </a>
                            <button type="submit" class="btn btn-del px-4">
                                <i class="bi bi-check2-circle me-1"></i> Simpan Perubahan
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</x-layout>
