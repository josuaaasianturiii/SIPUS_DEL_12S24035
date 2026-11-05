<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\Api\V1\BukuResource;
use App\Models\Buku;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;

class BukuApiController extends Controller
{
    use ApiResponse;

    /**
     * GET /api/v1/bukus (Publik)
     * Mengambil koleksi buku terpaginasi dengan pencarian, filter, dan pengurutan
     */
    public function index(Request $request): JsonResponse
    {
        // 1. Batasi pengurutan pada kolom yang sah (mencegah SQL Injection pada ORDER BY)
        $allowedSorts = ['judul', 'penulis', 'penerbit', 'tahun_terbit', 'stok', 'isbn', 'created_at'];
        $sortBy = in_array($request->query('sort'), $allowedSorts) ? $request->query('sort') : 'created_at';
        $sortDir = strtolower($request->query('dir', 'desc')) === 'asc' ? 'asc' : 'desc';
        $perPage = min(max((int) $request->query('per_page', 10), 1), 50);

        // 2. Kueri Eloquent bebas N+1 (eager loading relasi kategori dan count peminjamans)
        $paginator = Buku::query()
            ->with('kategori')
            ->withCount('peminjamans')
            ->when($request->query('search'), function ($q, $search) {
                $q->where(function ($sub) use ($search) {
                    $sub->where('judul', 'like', "%{$search}%")
                        ->orWhere('penulis', 'like', "%{$search}%")
                        ->orWhere('isbn', 'like', "%{$search}%");
                });
            })
            ->when($request->query('kategori_id'), fn ($q, $kId) => $q->where('kategori_id', $kId))
            ->when($request->has('tersedia'), function ($q) use ($request) {
                if ($request->boolean('tersedia')) {
                    $q->where('stok', '>', 0);
                } else {
                    $q->where('stok', '<=', 0);
                }
            })
            ->orderBy($sortBy, $sortDir)
            ->paginate($perPage)
            ->withQueryString();

        // 3. Kembalikan respons standar beserta metadata paginasi
        return $this->successResponse(
            data: BukuResource::collection($paginator->items()),
            message: 'Daftar koleksi katalog buku berhasil diambil.',
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
     * GET /api/v1/bukus/{identifier} (Publik)
     * Mengambil detail satu buku berdasarkan ID atau ISBN
     */
    public function show(string $identifier): JsonResponse
    {
        $buku = Buku::with(['kategori', 'peminjamans'])
            ->withCount('peminjamans')
            ->where('id', $identifier)
            ->orWhere('isbn', $identifier)
            ->first();

        if (!$buku) {
            return $this->problemResponse(
                title: 'Resource Not Found',
                detail: "Buku dengan pengenal '{$identifier}' tidak ditemukan di basis data.",
                status: 404,
                errorSlug: 'buku-not-found'
            );
        }

        return $this->successResponse(
            data: new BukuResource($buku),
            message: 'Detail buku berhasil ditemukan.'
        );
    }

    /**
     * POST /api/v1/bukus (Wajib auth:sanctum)
     * Menambah buku baru -> Mereturn 201 Created + Header Location
     */
    public function store(Request $request): JsonResponse
    {
        $currentYear = (int) date('Y') + 1;
        $validated = $request->validate([
            'isbn' => 'required|string|max:30|unique:bukus,isbn',
            'judul' => 'required|string|min:3|max:200',
            'penulis' => 'required|string|max:150',
            'penerbit' => 'required|string|max:150',
            'tahun_terbit' => "required|integer|min:1900|max:{$currentYear}",
            'kategori_id' => 'required|exists:kategoris,id',
            'stok' => 'required|integer|min:0|max:10000',
            'sinopsis' => 'nullable|string|max:2000',
        ]);

        $buku = Buku::create($validated);
        $buku->load('kategori')->loadCount('peminjamans');

        return $this->createdResponse(
            data: new BukuResource($buku),
            locationUrl: url("/api/v1/bukus/{$buku->id}"),
            message: 'Buku baru berhasil dibuat.'
        );
    }

    /**
     * PUT / PATCH /api/v1/bukus/{id} (Wajib auth:sanctum)
     * Membuktikan semantik PUT (Full Replacement) vs PATCH (Partial Update)
     */
    public function update(Request $request, string $id): JsonResponse
    {
        $buku = Buku::find($id);
        if (!$buku) {
            return $this->problemResponse(
                title: 'Resource Not Found',
                detail: "Buku dengan ID '{$id}' tidak ditemukan.",
                status: 404,
                errorSlug: 'buku-not-found'
            );
        }

        $currentYear = (int) date('Y') + 1;
        $isPut = $request->isMethod('put');

        if ($isPut) {
            // SEMANTIK PUT: Seluruh atribut representasi WAJIB dikirimkan
            $validated = $request->validate([
                'isbn' => "required|string|max:30|unique:bukus,isbn,{$buku->id}",
                'judul' => 'required|string|min:3|max:200',
                'penulis' => 'required|string|max:150',
                'penerbit' => 'required|string|max:150',
                'tahun_terbit' => "required|integer|min:1900|max:{$currentYear}",
                'kategori_id' => 'required|exists:kategoris,id',
                'stok' => 'required|integer|min:0|max:10000',
                'sinopsis' => 'nullable|string|max:2000',
            ]);

            // Pada PUT murni, jika field nullable (sinopsis) tidak dikirim, direset ke null
            $validated['sinopsis'] = $request->input('sinopsis', null);
        } else {
            // SEMANTIK PATCH: HANYA memvalidasi atribut yang dikirim saja
            $validated = $request->validate([
                'isbn' => "sometimes|required|string|max:30|unique:bukus,isbn,{$buku->id}",
                'judul' => 'sometimes|required|string|min:3|max:200',
                'penulis' => 'sometimes|required|string|max:150',
                'penerbit' => 'sometimes|required|string|max:150',
                'tahun_terbit' => "sometimes|required|integer|min:1900|max:{$currentYear}",
                'kategori_id' => 'sometimes|required|exists:kategoris,id',
                'stok' => 'sometimes|required|integer|min:0|max:10000',
                'sinopsis' => 'sometimes|nullable|string|max:2000',
            ]);

            if (empty($validated)) {
                return $this->problemResponse(
                    title: 'Bad Request',
                    detail: 'Permintaan PATCH wajib menyertakan minimal satu atribut yang ingin diperbarui.',
                    status: 400,
                    errorSlug: 'empty-patch-payload'
                );
            }
        }

        $buku->update($validated);
        $buku->load('kategori')->loadCount('peminjamans');

        return $this->successResponse(
            data: new BukuResource($buku),
            message: $isPut
                ? 'Seluruh representasi buku berhasil diganti (PUT).'
                : 'Atribut buku berhasil diperbarui sebagian (PATCH).'
        );
    }

    /**
     * DELETE /api/v1/bukus/{id} (Wajib auth:sanctum)
     * Menghapus buku -> 409 Conflict jika ada peminjaman aktif, 204 No Content jika sukses
     */
    public function destroy(string $id): JsonResponse
    {
        $buku = Buku::withCount(['peminjamans' => function ($q) {
            $q->where('status', 'dipinjam');
        }])->find($id);

        if (!$buku) {
            return $this->problemResponse(
                title: 'Resource Not Found',
                detail: "Buku dengan ID '{$id}' tidak ditemukan.",
                status: 404,
                errorSlug: 'buku-not-found'
            );
        }

        if ($buku->peminjamans_count > 0) {
            return $this->problemResponse(
                title: 'Conflict — Book Has Active Loans',
                detail: "Buku '{$buku->judul}' tidak dapat dihapus karena masih sedang dipinjam oleh {$buku->peminjamans_count} transaksi.",
                status: 409,
                errorSlug: 'book-has-active-loans'
            );
        }

        $buku->delete();

        return $this->noContentResponse();
    }

    /**
     * GET /api/v1/bukus/{id}/referensi-eksternal (Publik)
     * REST Service Consumer yang memanggil OpenLibrary API via Http Facade
     */
    public function referensiEksternal(string $id): JsonResponse
    {
        $buku = Buku::find($id);
        if (!$buku) {
            return $this->problemResponse(
                title: 'Resource Not Found',
                detail: "Buku dengan ID '{$id}' tidak ditemukan.",
                status: 404,
                errorSlug: 'buku-not-found'
            );
        }

        $rawIsbn = preg_replace('/[^0-9X]/i', '', $buku->isbn);
        $bibkey = "ISBN:{$rawIsbn}";

        try {
            $response = Http::timeout(8)
                ->acceptJson()
                ->withoutVerifying()
                ->get('https://openlibrary.org/api/books', [
                    'bibkeys' => $bibkey,
                    'format' => 'json',
                    'jscmd' => 'data',
                ]);

            if ($response->successful() && isset($response->json()[$bibkey])) {
                $ext = $response->json()[$bibkey];
                return $this->successResponse(
                    data: [
                        'local_book' => [
                            'id' => $buku->id,
                            'isbn' => $buku->isbn,
                            'judul' => $buku->judul,
                            'penulis' => $buku->penulis,
                        ],
                        'openlibrary_metadata' => [
                            'title' => $ext['title'] ?? $buku->judul,
                            'authors' => collect($ext['authors'] ?? [])->pluck('name'),
                            'publishers' => collect($ext['publishers'] ?? [])->pluck('name'),
                            'publish_date' => $ext['publish_date'] ?? null,
                            'number_of_pages' => $ext['number_of_pages'] ?? null,
                            'preview_url' => $ext['url'] ?? null,
                            'cover' => $ext['cover'] ?? null,
                        ],
                    ],
                    message: 'Metadata referensi eksternal buku berhasil diambil dari OpenLibrary API.'
                );
            }

            return $this->problemResponse(
                title: 'External Metadata Not Found',
                detail: "Data buku dengan ISBN '{$buku->isbn}' tidak ditemukan pada katalog OpenLibrary API.",
                status: 404,
                errorSlug: 'external-book-not-found'
            );
        } catch (\Throwable $e) {
            return $this->problemResponse(
                title: 'External Service Unavailable',
                detail: 'Gagal berkomunikasi dengan layanan OpenLibrary API (koneksi timeout / jaringan luar bermasalah).',
                status: 503,
                errorSlug: 'external-service-timeout'
            );
        }
    }
}
