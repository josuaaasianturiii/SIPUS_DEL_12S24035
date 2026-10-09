<x-layout>
    <x-slot:title>Tambah Kategori Baru</x-slot:title>

    <div class="row justify-content-center">
        <div class="col-lg-7">
            <div class="card card-custom">
                <div class="card-header card-header-del py-3">
                    <h5 class="card-title mb-0 fw-bold">
                        <i class="bi bi-folder-plus me-2"></i>Tambah Kategori Buku
                    </h5>
                </div>
                <div class="card-body p-4">
                    <form action="{{ route('kategori.store') }}" method="POST">
                        @csrf

                        <div class="mb-3">
                            <label for="kode_kategori" class="form-label fw-semibold">
                                Kode Kategori <span class="text-danger">*</span>
                            </label>
                            <input type="text" 
                                   name="kode_kategori" 
                                   id="kode_kategori" 
                                   class="form-control @error('kode_kategori') is-invalid @enderror" 
                                   value="{{ old('kode_kategori') }}" 
                                   placeholder="Contoh: CS, INF, MAT, BIO" 
                                   maxlength="10" 
                                   required>
                            @error('kode_kategori')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @else
                                <div class="form-text">Maksimal 10 karakter dan harus unik.</div>
                            @enderror
                        </div>

                        <div class="mb-4">
                            <label for="nama_kategori" class="form-label fw-semibold">
                                Nama Kategori <span class="text-danger">*</span>
                            </label>
                            <input type="text" 
                                   name="nama_kategori" 
                                   id="nama_kategori" 
                                   class="form-control @error('nama_kategori') is-invalid @enderror" 
                                   value="{{ old('nama_kategori') }}" 
                                   placeholder="Contoh: Ilmu Komputer & Pemrograman" 
                                   maxlength="100" 
                                   required>
                            @error('nama_kategori')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @else
                                <div class="form-text">Minimal 3 karakter, maksimal 100 karakter.</div>
                            @enderror
                        </div>

                        <div class="d-flex justify-content-between align-items-center pt-2">
                            <a href="{{ route('kategori.index') }}" class="btn btn-outline-secondary">
                                <i class="bi bi-arrow-left me-1"></i> Batal
                            </a>
                            <button type="submit" class="btn btn-del px-4">
                                <i class="bi bi-save me-1"></i> Simpan Kategori
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</x-layout>
