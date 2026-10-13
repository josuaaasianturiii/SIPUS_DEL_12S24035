@<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $title ?? 'SIPUS-Del' }} — Perpustakaan Institut Teknologi Del</title>
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Bootstrap Icons -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <style>
        :root {
            --del-purple: #4C1D95;
            --del-purple-hover: #3B0764;
            --del-purple-light: #7C3AED;
            --del-purple-subtle: #F3E8FF;
        }
        .navbar-del {
            background-color: var(--del-purple);
        }
        .bg-del-purple {
            background-color: var(--del-purple) !important;
            color: #ffffff;
        }
        .text-del-purple {
            color: var(--del-purple) !important;
        }
        .btn-del {
            background-color: var(--del-purple);
            color: #ffffff;
            border: 1px solid var(--del-purple);
            transition: all 0.2s ease-in-out;
        }
        .btn-del:hover, .btn-del:focus {
            background-color: var(--del-purple-hover);
            color: #ffffff;
            border-color: var(--del-purple-hover);
            transform: translateY(-1px);
        }
        .btn-outline-del {
            color: var(--del-purple);
            border-color: var(--del-purple);
        }
        .btn-outline-del:hover {
            background-color: var(--del-purple);
            color: #ffffff;
        }
        .badge-del {
            background-color: var(--del-purple-subtle);
            color: var(--del-purple);
            font-weight: 600;
        }
        .card-custom {
            border: none;
            border-radius: 12px;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.05);
            transition: transform 0.2s ease;
        }
        .card-header-del {
            background-color: var(--del-purple);
            color: #ffffff;
            border-top-left-radius: 12px !important;
            border-top-right-radius: 12px !important;
        }
        .pagination .page-link {
            color: var(--del-purple);
        }
        .pagination .page-item.active .page-link {
            background-color: var(--del-purple);
            border-color: var(--del-purple);
        }
        footer {
            background-color: #ffffff;
            border-top: 1px solid #e5e7eb;
        }
    </style>
</head>
<body class="bg-light d-flex flex-column min-vh-100">
    <!-- Navbar -->
    <nav class="navbar navbar-expand-lg navbar-dark navbar-del shadow-sm sticky-top">
        <div class="container">
            <a class="navbar-brand fw-bold d-flex align-items-center gap-2" href="{{ route('buku.index') }}">
                <i class="bi bi-book-half fs-4"></i>
                <span>SIPUS-Del</span>
            </a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarDelContent">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarDelContent">
                <ul class="navbar-nav me-auto mb-2 mb-lg-0">
                    <li class="nav-item">
                        <a class="nav-link {{ request()->routeIs('buku.*') ? 'active fw-semibold' : '' }}" href="{{ route('buku.index') }}">
                            <i class="bi bi-journal-text me-1"></i> Data Buku
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link {{ request()->routeIs('kategori.*') ? 'active fw-semibold' : '' }}" href="{{ route('kategori.index') }}">
                            <i class="bi bi-tags me-1"></i> Data Kategori
                        </a>
                    </li>
                </ul>
                <div class="d-flex gap-2">
                    <a href="{{ route('buku.create') }}" class="btn btn-sm btn-light text-del-purple fw-semibold shadow-sm">
                        <i class="bi bi-plus-circle-fill me-1"></i> Tambah Buku
                    </a>
                    <a href="{{ route('kategori.create') }}" class="btn btn-sm btn-outline-light fw-semibold">
                        <i class="bi bi-folder-plus me-1"></i> Tambah Kategori
                    </a>
                </div>
            </div>
        </div>
    </nav>

    <!-- Main Content Container -->
    <main class="container my-4 flex-grow-1">
        <!-- Flash Session Messages -->
        @if(session('success'))
            <div class="alert alert-success alert-dismissible fade show shadow-sm border-0 d-flex align-items-center gap-2" role="alert">
                <i class="bi bi-check-circle-fill fs-5"></i>
                <div>{{ session('success') }}</div>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        @endif

        @if(session('error'))
            <div class="alert alert-danger alert-dismissible fade show shadow-sm border-0 d-flex align-items-center gap-2" role="alert">
                <i class="bi bi-exclamation-triangle-fill fs-5"></i>
                <div>{{ session('error') }}</div>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        @endif

        <!-- Slot Content -->
        {{ $slot }}
    </main>

    <!-- Footer -->
    <footer class="py-3 mt-auto text-center text-muted small">
        <div class="container">
            <p class="mb-0 fw-medium">
                &copy; {{ date('Y') }} <strong>SIPUS-Del</strong> — Sistem Informasi Perpustakaan Kampus Del.
            </p>
            <p class="text-secondary mb-0">Pemrograman & Pengujian Web (12S3101) &bull; Institut Teknologi Del</p>
        </div>
    </footer>

    <!-- Bootstrap 5 Bundle JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
