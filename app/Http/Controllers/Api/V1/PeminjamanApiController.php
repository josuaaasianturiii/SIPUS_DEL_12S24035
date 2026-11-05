<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\Api\V1\PeminjamanResource;
use App\Models\Buku;
use App\Models\Peminjaman;
use App\Traits\ApiResponse;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class PeminjamanApiController extends Controller
{
    use ApiResponse;

    /**
     * GET /api/v1/peminjamans (Wajib auth:sanctum)
     * Mengambil daftar transaksi peminjaman terpaginasi dengan filter
     */
    public function index(Request $request): JsonResponse
    {
        $status = $request->query('status');
        $nim = $request->query('nim');
        $search = $request->query('search');
        $perPage = min(max((int) $request->query('per_page', 10), 1), 50);

        $paginator = Peminjaman::query()
            ->with('buku')
            ->when($status, fn ($q, $s) => $q->where('status', $s))
            ->when($nim, fn ($q, $n) => $q->where('nim_peminjam', strtoupper(trim($n))))
            ->when($search, function ($q, $s) {
                $q->where(function ($sub) use ($s) {
                    $sub->where('kode_pinjam', 'like', "%{$s}%")
                        ->orWhere('nim_peminjam', 'like', "%{$s}%")
                        ->orWhere('nama_peminjam', 'like', "%{$s}%")
                        ->orWhereHas('buku', fn ($b) => $b->where('judul', 'like', "%{$s}%"));
                });
            })
            ->latest()
            ->paginate($perPage)
            ->withQueryString();

        return $this->successResponse(
            data: PeminjamanResource::collection($paginator->items()),
            message: 'Daftar riwayat transaksi peminjaman berhasil diambil.',
            meta: [
                'current_page' => $paginator->currentPage(),
                'per_page' => $paginator->perPage(),
                'total_items' => $paginator->total(),
                'total_pages' => $paginator->lastPage(),
                'links' => [
                    'first' => $paginator->url(1),
                    'prev' => $paginator->previousPageUrl(),
                    'next' => $paginator->nextPageUrl(),
                    'last' => $paginator->url($paginator->lastPage()),
                ],
            ]
        );
    }

    /**
     * GET /api/v1/peminjamans/{id} (Wajib auth:sanctum)
     */
    public function show(string $id): JsonResponse
    {
        $peminjaman = Peminjaman::with('buku')->find($id);

        if (!$peminjaman) {
            return $this->problemResponse(
                title: 'Resource Not Found',
                detail: "Data peminjaman dengan ID '{$id}' tidak ditemukan.",
                status: 404,
                errorSlug: 'peminjaman-not-found'
            );
        }

        return $this->successResponse(
            data: new PeminjamanResource($peminjaman),
            message: 'Detail transaksi peminjaman berhasil ditemukan.'
        );
    }

    /**
     * POST /api/v1/peminjamans (Wajib auth:sanctum)
     * Mencatat peminjaman buku baru dengan Transaksi ACID & Pessimistic Locking (lockForUpdate)
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'buku_id' => 'required|integer',
            'nim_peminjam' => 'required|string|min:6|max:15',
            'nama_peminjam' => 'required|string|min:3|max:150',
            'durasi_hari' => 'nullable|integer|min:1|max:30',
            'tanggal_pinjam' => 'nullable|date',
            'tanggal_jatuh_tempo' => 'nullable|date|after_or_equal:tanggal_pinjam',
        ]);

        $nim = strtoupper(trim($validated['nim_peminjam']));
        $nama = trim($validated['nama_peminjam']);
        $bukuId = (int) $validated['buku_id'];
        $durasiHari = (int) ($validated['durasi_hari'] ?? 7);

        $tglPinjam = isset($validated['tanggal_pinjam'])
            ? Carbon::parse($validated['tanggal_pinjam'])
            : Carbon::today();

        $tglJatuhTempo = isset($validated['tanggal_jatuh_tempo'])
            ? Carbon::parse($validated['tanggal_jatuh_tempo'])
            : (clone $tglPinjam)->addDays($durasiHari);

        return DB::transaction(function () use ($bukuId, $nim, $nama, $tglPinjam, $tglJatuhTempo) {
            // 1. Kunci baris buku secara eksklusif (SELECT ... FOR UPDATE) untuk menangkal Race Condition
            $buku = Buku::where('id', $bukuId)->lockForUpdate()->first();

            if (!$buku) {
                return $this->problemResponse(
                    title: 'Resource Not Found',
                    detail: "Buku dengan ID '{$bukuId}' tidak ditemukan.",
                    status: 404,
                    errorSlug: 'buku-not-found'
                );
            }

            // 2. Cek apakah mahasiswa masih meminjam buku yang sama dan belum dikembalikan -> 409 Conflict
            $alreadyBorrowing = Peminjaman::where('buku_id', $buku->id)
                ->where('nim_peminjam', $nim)
                ->where('status', 'dipinjam')
                ->lockForUpdate()
                ->exists();

            if ($alreadyBorrowing) {
                return $this->problemResponse(
                    title: 'Duplicate Borrowing Conflict',
                    detail: "Mahasiswa dengan NIM '{$nim}' saat ini masih meminjam buku '{$buku->judul}' dan belum mengembalikannya.",
                    status: 409,
                    errorSlug: 'duplicate-active-loan'
                );
            }

            // 3. Periksa ketersediaan sisa stok buku -> 422 Unprocessable Content jika habis
            if ($buku->stok < 1) {
                return $this->problemResponse(
                    title: 'Book Stock Exhausted',
                    detail: "Stok buku '{$buku->judul}' sedang habis (0 tersisa). Transaksi peminjaman ditolak.",
                    status: 422,
                    errorSlug: 'book-out-of-stock'
                );
            }

            // 4. Kurangi stok buku (-1) secara atomik
            $buku->decrement('stok');

            // 5. Catat riwayat peminjaman baru
            $kodePinjam = 'PJM-' . date('Ymd') . '-' . strtoupper(Str::random(5));
            $peminjaman = Peminjaman::create([
                'kode_pinjam' => $kodePinjam,
                'buku_id' => $buku->id,
                'nim_peminjam' => $nim,
                'nama_peminjam' => $nama,
                'tanggal_pinjam' => $tglPinjam->toDateString(),
                'tanggal_jatuh_tempo' => $tglJatuhTempo->toDateString(),
                'status' => 'dipinjam',
            ]);

            $peminjaman->load('buku');

            return $this->createdResponse(
                data: new PeminjamanResource($peminjaman),
                locationUrl: url("/api/v1/peminjamans/{$peminjaman->id}"),
                message: "Peminjaman buku '{$buku->judul}' berhasil dicatat dengan kode: {$kodePinjam}. Sisa stok kini: {$buku->fresh()->stok}."
            );
        }, 3);
    }

    /**
     * PATCH /api/v1/peminjamans/{id}/kembalikan (Wajib auth:sanctum)
     * Mengembalikan buku dengan Transaksi ACID & lockForUpdate
     */
    public function kembalikan(string $id): JsonResponse
    {
        return DB::transaction(function () use ($id) {
            // 1. Kunci baris transaksi peminjaman
            $peminjaman = Peminjaman::where('id', $id)->lockForUpdate()->first();

            if (!$peminjaman) {
                return $this->problemResponse(
                    title: 'Resource Not Found',
                    detail: "Data peminjaman dengan ID '{$id}' tidak ditemukan.",
                    status: 404,
                    errorSlug: 'peminjaman-not-found'
                );
            }

            // 2. Cek apakah buku sudah pernah dikembalikan -> 409 Conflict
            if ($peminjaman->status === 'dikembalikan') {
                return $this->problemResponse(
                    title: 'Loan Already Returned Conflict',
                    detail: "Buku dengan kode peminjaman {$peminjaman->kode_pinjam} sudah pernah dikembalikan sebelumnya.",
                    status: 409,
                    errorSlug: 'loan-already-returned'
                );
            }

            // 3. Kunci baris buku terkait
            $buku = Buku::where('id', $peminjaman->buku_id)->lockForUpdate()->first();

            // 4. Ubah status peminjaman menjadi 'dikembalikan' dan catat tanggal kembali
            $peminjaman->update([
                'status' => 'dikembalikan',
                'tanggal_kembali' => Carbon::today()->toDateString(),
            ]);

            // 5. Kembalikan stok buku (+1) secara atomik
            if ($buku) {
                $buku->increment('stok');
            }

            $peminjaman->load('buku');

            return $this->successResponse(
                data: new PeminjamanResource($peminjaman),
                message: "Buku '{$peminjaman->buku->judul}' berhasil dikembalikan. Stok buku telah bertambah menjadi {$buku->fresh()->stok}."
            );
        }, 3);
    }
}
