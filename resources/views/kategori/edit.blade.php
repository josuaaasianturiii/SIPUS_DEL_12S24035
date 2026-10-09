<x-layout>
    <x-slot:title>Ubah Kategori — {{ $kategori->nama_kategori }}</x-slot:title>

    <div class="row justify-content-center">
        <div class="col-lg-7">
            <div class="card card-custom">
                <div class="card-header card-header-del py-3">
                    <h5 class="card-title mb-0 fw-bold">
                        <i class="bi bi-pencil-square me-2"></i>Ubah Data Kategori
                    </h5>
                </div>
                <div class="card-body p-4">
                    <form action="{{ route('kategori.update', $kategori) }}" method="POST">
                        @csrf
                        @method('PUT')

                        <div class="mb-3">
                            <label for="kode_kategori" class="form-label fw-semibold">
                                Kode Kategori <span class="text-danger">*</span>
                            </label>
                            <input type="text" 
                                   name="kode_kategori" 
                                   id="kode_kategori" 
                                   class="form-control @error('kode_kategori') is-invalid @enderror" 
                                   value="{{ old('kode_kategori', $kategori->kode_kategori) }}" 
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
                                   value="{{ old('nama_kategori', $kategori->nama_kategori) }}" 
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
                                <i class="bi bi-check2-circle me-1"></i> Simpan Perubahan
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</x-layout>
