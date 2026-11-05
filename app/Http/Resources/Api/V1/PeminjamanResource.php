<?php

namespace App\Http\Resources\Api\V1;

use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PeminjamanResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     */
    public function toArray(Request $request): array
    {
        $links = [
            'self' => [
                'href' => url("/api/v1/peminjamans/{$this->id}"),
                'method' => 'GET',
            ],
            'buku_detail' => [
                'href' => url("/api/v1/bukus/{$this->buku_id}"),
                'method' => 'GET',
            ],
        ];

        // HATEOAS: Link kembalikan_buku HANYA muncul jika status masih 'dipinjam'
        if ($this->status === 'dipinjam') {
            $links['kembalikan_buku'] = [
                'href' => url("/api/v1/peminjamans/{$this->id}/kembalikan"),
                'method' => 'PATCH',
            ];
        }

        $tanggalPinjam = $this->tanggal_pinjam instanceof Carbon
            ? $this->tanggal_pinjam->format('Y-m-d')
            : (string) $this->tanggal_pinjam;

        $tanggalJatuhTempo = $this->tanggal_jatuh_tempo instanceof Carbon
            ? $this->tanggal_jatuh_tempo->format('Y-m-d')
            : (string) $this->tanggal_jatuh_tempo;

        $tanggalKembali = $this->tanggal_kembali
            ? ($this->tanggal_kembali instanceof Carbon ? $this->tanggal_kembali->format('Y-m-d') : (string) $this->tanggal_kembali)
            : null;

        return [
            'id' => $this->id,
            'kode_pinjam' => $this->kode_pinjam,
            'buku_id' => $this->buku_id,
            'buku' => $this->whenLoaded('buku', fn () => [
                'id' => $this->buku->id,
                'isbn' => $this->buku->isbn,
                'judul' => $this->buku->judul,
                'penulis' => $this->buku->penulis,
            ]),
            'nim_peminjam' => $this->nim_peminjam,
            'nama_peminjam' => $this->nama_peminjam,
            'tanggal_pinjam' => $tanggalPinjam,
            'tanggal_jatuh_tempo' => $tanggalJatuhTempo,
            'tanggal_kembali' => $tanggalKembali,
            'status' => $this->status,
            'created_at' => $this->created_at?->toIso8601String(),
            '_links' => $links,
        ];
    }
}
