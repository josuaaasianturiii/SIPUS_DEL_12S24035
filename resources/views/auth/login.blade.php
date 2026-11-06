<x-layout>
    <x-slot:title>Login Pustakawan</x-slot:title>

    <div class="row justify-content-center my-4">
        <div class="col-md-6 col-lg-5">
            <div class="card card-custom shadow-sm border-0">
                <div class="card-header bg-del-purple text-white text-center py-4">
                    <div class="d-inline-flex p-3 rounded-circle bg-white text-del-purple mb-2 shadow-sm">
                        <i class="bi bi-shield-lock-fill fs-2"></i>
                    </div>
                    <h4 class="fw-bold mb-1">Login Pustakawan</h4>
                    <p class="small text-white-50 mb-0">Portal Autentikasi Pengelola Perpustakaan IT Del</p>
                </div>
                <div class="card-body p-4">
                    <form action="{{ route('login') }}" method="POST">
                        @csrf

                        <!-- Email Input -->
                        <div class="mb-3">
                            <label for="email" class="form-label fw-semibold">Alamat Email Pustakawan</label>
                            <div class="input-group">
                                <span class="input-group-text bg-light"><i class="bi bi-envelope-fill text-muted"></i></span>
                                <input type="email" name="email" id="email" 
                                    class="form-control @error('email') is-invalid @enderror" 
                                    placeholder="nama@del.ac.id" 
                                    value="{{ old('email', $rememberedEmail) }}" required autofocus>
                            </div>
                            @error('email')
                                <div class="text-danger small mt-1">{{ $message }}</div>
                            @enderror
                        </div>

                        <!-- Password Input -->
                        <div class="mb-3">
                            <label for="password" class="form-label fw-semibold">Kata Sandi</label>
                            <div class="input-group">
                                <span class="input-group-text bg-light"><i class="bi bi-key-fill text-muted"></i></span>
                                <input type="password" name="password" id="password" 
                                    class="form-control @error('password') is-invalid @enderror" 
                                    placeholder="Masukkan kata sandi Anda" required>
                            </div>
                            @error('password')
                                <div class="text-danger small mt-1">{{ $message }}</div>
                            @enderror
                        </div>

                        <!-- Remember Me Checkbox -->
                        <div class="mb-3 form-check">
                            <input type="checkbox" name="remember" id="remember" class="form-check-input" {{ old('remember') ? 'checked' : '' }}>
                            <label class="form-check-label small text-secondary" for="remember">
                                Ingat email saya di perangkat ini (Cookie 7 Hari)
                            </label>
                        </div>

                        <!-- Submit Button -->
                        <button type="submit" class="btn btn-del w-100 py-2 fw-semibold shadow-sm mb-3">
                            <i class="bi bi-box-arrow-in-right me-1"></i> Masuk ke Sistem
                        </button>
                    </form>

                    <!-- Info Akun Uji Coba Default -->
                    <div class="alert alert-light border small text-muted mb-0">
                        <div class="fw-bold text-dark d-flex align-items-center gap-1 mb-1">
                            <i class="bi bi-info-circle-fill text-del-purple"></i>
                            <span>Akun Pengujian Pustakawan:</span>
                        </div>
                        <ul class="mb-0 ps-3">
                            <li>Email: <code class="text-dark">pustakawan@del.ac.id</code></li>
                            <li>Password: <code class="text-dark">password123</code></li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-layout>
