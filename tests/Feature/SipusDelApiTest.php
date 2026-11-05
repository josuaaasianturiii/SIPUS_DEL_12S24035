<?php

namespace Tests\Feature;

use App\Models\Buku;
use App\Models\Kategori;
use App\Models\Peminjaman;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class SipusDelApiTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;
    protected Kategori $kategori;
    protected string $token;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create([
            'email' => 'pustakawan@del.ac.id',
            'password' => bcrypt('password123'),
        ]);

        $this->token = $this->user->createToken('test-token')->plainTextToken;

        $this->kategori = Kategori::create([
            'kode_kategori' => 'RPL',
            'nama_kategori' => 'Rekayasa Perangkat Lunak',
        ]);
    }

    /**
     * LAB 1 / Section 5.3.1: SOAP Clearance Check tests
     */
    public function test_soap_wsdl_endpoint_is_accessible(): void
    {
        $response = $this->get('/soap/sipus.wsdl');

        $response->assertStatus(200);
        $response->assertHeader('Content-Type', 'text/xml; charset=utf-8');
        $this->assertStringContainsString('LibrarySoapService', $response->getContent());
        $this->assertStringContainsString('CheckClearanceStatus', $response->getContent());
    }

    public function test_soap_endpoint_returns_cleared_when_no_active_loans(): void
    {
        $xmlPayload = <<<XML
<?xml version="1.0" encoding="UTF-8"?>
<soap:Envelope xmlns:soap="http://schemas.xmlsoap.org/soap/envelope/" xmlns:m="http://sipus.del.ac.id/soap/library">
    <soap:Body>
        <m:CheckClearanceStatusRequest>
            <m:nim_peminjam>12S24035</m:nim_peminjam>
        </m:CheckClearanceStatusRequest>
    </soap:Body>
</soap:Envelope>
XML;

        $response = $this->call(
            'POST',
            '/soap/sipus',
            [],
            [],
            [],
            ['CONTENT_TYPE' => 'text/xml; charset=utf-8'],
            $xmlPayload
        );

        $response->assertStatus(200);
        $this->assertStringContainsString('<m:is_cleared>true</m:is_cleared>', $response->getContent());
        $this->assertStringContainsString('<m:active_loans>0</m:active_loans>', $response->getContent());
    }

    public function test_soap_endpoint_returns_not_cleared_when_active_loan_exists(): void
    {
        $buku = Buku::create([
            'isbn' => '978-602-0001-01-1',
            'judul' => 'Arsitektur Web Services',
            'penulis' => 'Chandro Pardede',
            'penerbit' => 'Del Press',
            'tahun_terbit' => 2026,
            'kategori_id' => $this->kategori->id,
            'stok' => 5,
        ]);

        Peminjaman::create([
            'kode_pinjam' => 'PJM-20261105-00001',
            'buku_id' => $buku->id,
            'nim_peminjam' => '12S24035',
            'nama_peminjam' => 'Josua Sianturi',
            'tanggal_pinjam' => now()->toDateString(),
            'tanggal_jatuh_tempo' => now()->addDays(7)->toDateString(),
            'status' => 'dipinjam',
        ]);

        $xmlPayload = <<<XML
<?xml version="1.0" encoding="UTF-8"?>
<soap:Envelope xmlns:soap="http://schemas.xmlsoap.org/soap/envelope/" xmlns:m="http://sipus.del.ac.id/soap/library">
    <soap:Body>
        <m:CheckClearanceStatusRequest>
            <m:nim_peminjam>12S24035</m:nim_peminjam>
        </m:CheckClearanceStatusRequest>
    </soap:Body>
</soap:Envelope>
XML;

        $response = $this->call(
            'POST',
            '/soap/sipus',
            [],
            [],
            [],
            ['CONTENT_TYPE' => 'text/xml; charset=utf-8'],
            $xmlPayload
        );

        $response->assertStatus(200);
        $this->assertStringContainsString('<m:is_cleared>false</m:is_cleared>', $response->getContent());
        $this->assertStringContainsString('<m:active_loans>1</m:active_loans>', $response->getContent());
    }

    public function test_soap_endpoint_returns_fault_for_empty_or_malformed_xml(): void
    {
        $response = $this->call(
            'POST',
            '/soap/sipus',
            [],
            [],
            [],
            ['CONTENT_TYPE' => 'text/xml; charset=utf-8'],
            'MALFORMED XML CONTENT'
        );

        $response->assertStatus(500);
        $this->assertStringContainsString('<faultcode>soap:Client</faultcode>', $response->getContent());
    }

    /**
     * LAB 4 / Section 5.3.3: Authentication tests
     */
    public function test_auth_login_issues_bearer_token(): void
    {
        $response = $this->postJson('/api/v1/auth/login', [
            'email' => 'pustakawan@del.ac.id',
            'password' => 'password123',
        ]);

        $response->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonStructure([
                'success',
                'message',
                'data' => [
                    'token_type',
                    'access_token',
                    'user' => ['id', 'name', 'email'],
                ],
            ]);
    }

    public function test_auth_login_fails_with_invalid_credentials(): void
    {
        $response = $this->postJson('/api/v1/auth/login', [
            'email' => 'pustakawan@del.ac.id',
            'password' => 'wrongpassword',
        ]);

        $response->assertStatus(401)
            ->assertHeader('Content-Type', 'application/problem+json')
            ->assertJsonPath('title', 'Invalid Credentials');
    }

    public function test_auth_me_and_logout_endpoints(): void
    {
        // me
        $meResponse = $this->withHeader('Authorization', "Bearer {$this->token}")
            ->getJson('/api/v1/auth/me');

        $meResponse->assertStatus(200)
            ->assertJsonPath('data.email', 'pustakawan@del.ac.id');

        // logout
        $logoutResponse = $this->withHeader('Authorization', "Bearer {$this->token}")
            ->postJson('/api/v1/auth/logout');

        $logoutResponse->assertStatus(200)
            ->assertJsonPath('success', true);
    }

    /**
     * LAB 3 & 4 / Section 5.3.2: Buku REST API & HATEOAS tests
     */
    public function test_get_bukus_collection_with_hateoas(): void
    {
        Buku::create([
            'isbn' => '978-602-0001-02-8',
            'judul' => 'Clean Architecture',
            'penulis' => 'Robert C. Martin',
            'penerbit' => 'Prentice Hall',
            'tahun_terbit' => 2018,
            'kategori_id' => $this->kategori->id,
            'stok' => 5,
        ]);

        $response = $this->getJson('/api/v1/bukus');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'message',
                'data' => [
                    '*' => [
                        'id',
                        'isbn',
                        'judul',
                        'stok',
                        'status_label',
                        '_links' => [
                            'self',
                            'pinjam_buku',
                        ],
                    ],
                ],
                'meta' => [
                    'current_page',
                    'per_page',
                    'total_items',
                ],
            ]);
    }

    public function test_hateoas_pinjam_buku_disappears_when_stock_is_zero(): void
    {
        $buku = Buku::create([
            'isbn' => '978-602-0001-03-5',
            'judul' => 'Out of Stock Book',
            'penulis' => 'Author A',
            'penerbit' => 'Publisher B',
            'tahun_terbit' => 2024,
            'kategori_id' => $this->kategori->id,
            'stok' => 0,
        ]);

        $response = $this->getJson("/api/v1/bukus/{$buku->id}");

        $response->assertStatus(200)
            ->assertJsonPath('data.status_label', 'OUT_OF_STOCK');

        $this->assertArrayNotHasKey('pinjam_buku', $response->json('data._links'));
    }

    public function test_create_buku_requires_token_and_returns_201_with_location_header(): void
    {
        // Unauthenticated -> 401 RFC 7807
        $unauth = $this->postJson('/api/v1/bukus', [
            'isbn' => '978-602-0001-04-2',
            'judul' => 'Domain-Driven Design',
        ]);
        $unauth->assertStatus(401)
            ->assertHeader('Content-Type', 'application/problem+json');

        // Authenticated with valid payload -> 201 Created
        $response = $this->withHeader('Authorization', "Bearer {$this->token}")
            ->postJson('/api/v1/bukus', [
                'isbn' => '978-602-0001-04-2',
                'judul' => 'Domain-Driven Design',
                'penulis' => 'Eric Evans',
                'penerbit' => 'Addison-Wesley',
                'tahun_terbit' => 2004,
                'kategori_id' => $this->kategori->id,
                'stok' => 10,
                'sinopsis' => 'Tackling Complexity in the Heart of Software',
            ]);

        $response->assertStatus(201)
            ->assertHeader('Location', url('/api/v1/bukus/' . $response->json('data.id')))
            ->assertJsonPath('data.judul', 'Domain-Driven Design');
    }

    public function test_put_vs_patch_buku_semantics(): void
    {
        $buku = Buku::create([
            'isbn' => '978-602-0001-05-9',
            'judul' => 'Refactoring',
            'penulis' => 'Martin Fowler',
            'penerbit' => 'Addison-Wesley',
            'tahun_terbit' => 2019,
            'kategori_id' => $this->kategori->id,
            'stok' => 4,
            'sinopsis' => 'Improving the Design of Existing Code',
        ]);

        // PATCH: partial update (only update stok)
        $patchResponse = $this->withHeader('Authorization', "Bearer {$this->token}")
            ->patchJson("/api/v1/bukus/{$buku->id}", [
                'stok' => 8,
            ]);

        $patchResponse->assertStatus(200)
            ->assertJsonPath('data.stok', 8)
            ->assertJsonPath('data.sinopsis', 'Improving the Design of Existing Code');

        // PUT: full replacement (omitting sinopsis resets it to null)
        $putResponse = $this->withHeader('Authorization', "Bearer {$this->token}")
            ->putJson("/api/v1/bukus/{$buku->id}", [
                'isbn' => '978-602-0001-05-9',
                'judul' => 'Refactoring 2nd Edition',
                'penulis' => 'Martin Fowler',
                'penerbit' => 'Addison-Wesley',
                'tahun_terbit' => 2019,
                'kategori_id' => $this->kategori->id,
                'stok' => 8,
            ]);

        $putResponse->assertStatus(200)
            ->assertJsonPath('data.judul', 'Refactoring 2nd Edition')
            ->assertJsonPath('data.sinopsis', null);
    }

    public function test_delete_buku_rejected_when_active_loan_exists(): void
    {
        $buku = Buku::create([
            'isbn' => '978-602-0001-06-6',
            'judul' => 'Database Internals',
            'penulis' => 'Alex Petrov',
            'penerbit' => "O'Reilly",
            'tahun_terbit' => 2019,
            'kategori_id' => $this->kategori->id,
            'stok' => 2,
        ]);

        Peminjaman::create([
            'kode_pinjam' => 'PJM-20261105-00002',
            'buku_id' => $buku->id,
            'nim_peminjam' => '12S24035',
            'nama_peminjam' => 'Josua Sianturi',
            'tanggal_pinjam' => now()->toDateString(),
            'tanggal_jatuh_tempo' => now()->addDays(7)->toDateString(),
            'status' => 'dipinjam',
        ]);

        // Attempt DELETE -> 409 Conflict
        $response = $this->withHeader('Authorization', "Bearer {$this->token}")
            ->deleteJson("/api/v1/bukus/{$buku->id}");

        $response->assertStatus(409)
            ->assertHeader('Content-Type', 'application/problem+json')
            ->assertJsonPath('title', 'Conflict — Book Has Active Loans');

        // After returning the book, DELETE succeeds with 204 No Content
        Peminjaman::where('buku_id', $buku->id)->update(['status' => 'dikembalikan']);

        $deleteResponse = $this->withHeader('Authorization', "Bearer {$this->token}")
            ->deleteJson("/api/v1/bukus/{$buku->id}");

        $deleteResponse->assertStatus(204);
        $this->assertDatabaseMissing('bukus', ['id' => $buku->id]);
    }

    /**
     * LAB 4 / Section 5.3.3: Peminjaman ACID Transaksi & lockForUpdate tests
     */
    public function test_peminjaman_and_return_acid_lifecycle(): void
    {
        $buku = Buku::create([
            'isbn' => '978-602-0001-07-3',
            'judul' => 'Designing Data-Intensive Applications',
            'penulis' => 'Martin Kleppmann',
            'penerbit' => "O'Reilly",
            'tahun_terbit' => 2017,
            'kategori_id' => $this->kategori->id,
            'stok' => 1,
        ]);

        // 1. Borrow book -> succeeds, stock becomes 0
        $borrowResponse = $this->withHeader('Authorization', "Bearer {$this->token}")
            ->postJson('/api/v1/peminjamans', [
                'buku_id' => $buku->id,
                'nim_peminjam' => '12S24035',
                'nama_peminjam' => 'Josua Sianturi',
                'durasi_hari' => 7,
            ]);

        $borrowResponse->assertStatus(201)
            ->assertHeader('Location', url('/api/v1/peminjamans/' . $borrowResponse->json('data.id')))
            ->assertJsonPath('data.status', 'dipinjam');

        $peminjamanId = $borrowResponse->json('data.id');
        $this->assertEquals(0, $buku->fresh()->stok);

        // 2. Duplicate loan for same student -> 409 Conflict
        $dupResponse = $this->withHeader('Authorization', "Bearer {$this->token}")
            ->postJson('/api/v1/peminjamans', [
                'buku_id' => $buku->id,
                'nim_peminjam' => '12S24035',
                'nama_peminjam' => 'Josua Sianturi',
            ]);
        $dupResponse->assertStatus(409)
            ->assertJsonPath('title', 'Duplicate Borrowing Conflict');

        // 3. Another student borrows when stock is 0 -> 422 Unprocessable
        $stockOutResponse = $this->withHeader('Authorization', "Bearer {$this->token}")
            ->postJson('/api/v1/peminjamans', [
                'buku_id' => $buku->id,
                'nim_peminjam' => '12S24099',
                'nama_peminjam' => 'Another Student',
            ]);
        $stockOutResponse->assertStatus(422)
            ->assertJsonPath('title', 'Book Stock Exhausted');

        // 4. Return book -> status becomes 'dikembalikan', stock increases back to 1
        $returnResponse = $this->withHeader('Authorization', "Bearer {$this->token}")
            ->patchJson("/api/v1/peminjamans/{$peminjamanId}/kembalikan");

        $returnResponse->assertStatus(200)
            ->assertJsonPath('data.status', 'dikembalikan');

        $this->assertEquals(1, $buku->fresh()->stok);

        // 5. Try returning again -> 409 Conflict
        $reReturnResponse = $this->withHeader('Authorization', "Bearer {$this->token}")
            ->patchJson("/api/v1/peminjamans/{$peminjamanId}/kembalikan");

        $reReturnResponse->assertStatus(409)
            ->assertJsonPath('title', 'Loan Already Returned Conflict');
    }

    /**
     * Section 5.3.4: External REST Service Consumer test
     */
    public function test_external_book_reference_consumer_endpoint(): void
    {
        $buku = Buku::create([
            'isbn' => '9780132350884',
            'judul' => 'Clean Code',
            'penulis' => 'Robert C. Martin',
            'penerbit' => 'Prentice Hall',
            'tahun_terbit' => 2008,
            'kategori_id' => $this->kategori->id,
            'stok' => 5,
        ]);

        Http::fake([
            'https://openlibrary.org/api/books*' => Http::response([
                'ISBN:9780132350884' => [
                    'title' => 'Clean Code: A Handbook of Agile Software Craftsmanship',
                    'authors' => [['name' => 'Robert C. Martin']],
                    'publishers' => [['name' => 'Prentice Hall']],
                    'publish_date' => 'August 1, 2008',
                    'number_of_pages' => 464,
                    'url' => 'https://openlibrary.org/books/OL112233M',
                ],
            ], 200),
        ]);

        $response = $this->getJson("/api/v1/bukus/{$buku->id}/referensi-eksternal");

        $response->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.openlibrary_metadata.number_of_pages', 464);
    }
}
