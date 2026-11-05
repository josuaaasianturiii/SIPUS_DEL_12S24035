<?php

namespace App\Http\Resources\Api\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class BukuResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     */
    public function toArray(Request $request): array
    {
        $enrolledCount = $this->whenCounted('peminjamans', fn () => $this->peminjamans_count, 0);
        $isAvailable = $this->stok > 0;

        // Kontrol Hypermedia HATEOAS (Richardson Maturity Model Level 3)
        $links = [
            'self' => [
                'href' => url("/api/v1/bukus/{$this->id}"),
                'method' => 'GET',
            ],
            'referensi_eksternal' => [
                'href' => url("/api/v1/bukus/{$this->id}/referensi-eksternal"),
                'method' => 'GET',
            ],
            'update_full' => [
                'href' => url("/api/v1/bukus/{$this->id}"),
                'method' => 'PUT',
            ],
            'update_partial' => [
                'href' => url("/api/v1/bukus/{$this->id}"),
                'method' => 'PATCH',
            ],
            'delete' => [
                'href' => url("/api/v1/bukus/{$this->id}"),
                'method' => 'DELETE',
            ],
        ];

        // HATEOAS Dinamis: Link aksi 'pinjam_buku' HANYA muncul jika stok masih tersedia (> 0)
        if ($isAvailable) {
            $links['pinjam_buku'] = [
                'href' => url('/api/v1/peminjamans'),
                'method' => 'POST',
            ];
        }

        return [
            'id' => $this->id,
            'isbn' => $this->isbn,
            'judul' => $this->judul,
            'penulis' => $this->penulis,
            'penerbit' => $this->penerbit,
            'tahun_terbit' => (int) $this->tahun_terbit,
            'stok' => (int) $this->stok,
            'status_label' => $isAvailable ? 'AVAILABLE' : 'OUT_OF_STOCK',
            'sinopsis' => $this->sinopsis,
            'kategori' => $this->whenLoaded('kategori', fn () => [
                'id' => $this->kategori->id,
                'kode_kategori' => $this->kategori->kode_kategori,
                'nama_kategori' => $this->kategori->nama_kategori,
            ]),
            'total_peminjaman' => $enrolledCount,
            'riwayat_peminjaman' => PeminjamanResource::collection($this->whenLoaded('peminjamans')),
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
            '_links' => $links,
        ];
    }
}
