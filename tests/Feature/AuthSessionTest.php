<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class AuthSessionTest extends TestCase
{
    use RefreshDatabase;

    private function createLibrarian(): User
    {
        return User::create([
            'name' => 'Pustakawan Institut Teknologi Del',
            'email' => 'pustakawan@del.ac.id',
            'password' => Hash::make('password123'),
        ]);
    }

    /**
     * Uji 1: Halaman formulir login dapat diakses oleh pengunjung (guest).
     */
    public function test_login_screen_can_be_rendered(): void
    {
        $response = $this->get(route('login'));

        $response->assertStatus(200);
        $response->assertSee('Login Pustakawan');
        $response->assertSee('pustakawan@del.ac.id');
    }

    /**
     * Uji 2: Pustakawan berhasil login dengan kredensial valid, regenerasi ID sesi, dan antrean cookie.
     */
    public function test_librarian_can_authenticate_with_session_regeneration_and_cookies(): void
    {
        $user = $this->createLibrarian();

        $response = $this->post(route('login'), [
            'email' => 'pustakawan@del.ac.id',
            'password' => 'password123',
            'remember' => true,
        ]);

        $this->assertAuthenticatedAs($user);
        $response->assertSessionHas('login_timestamp');
        $response->assertCookie('del_last_user_email', 'pustakawan@del.ac.id');
        $response->assertRedirect(route('buku.index'));
    }

    /**
     * Uji 3: Login ditolak jika kata sandi salah.
     */
    public function test_librarian_cannot_authenticate_with_invalid_password(): void
    {
        $this->createLibrarian();

        $response = $this->post(route('login'), [
            'email' => 'pustakawan@del.ac.id',
            'password' => 'wrong-password',
        ]);

        $this->assertGuest();
        $response->assertSessionHas('error');
    }

    /**
     * Uji 4: Logout berhasil memusnahkan sesi dan mengarahkan kembali ke halaman login.
     */
    public function test_librarian_can_logout_and_session_is_invalidated(): void
    {
        $user = $this->createLibrarian();

        $response = $this->actingAs($user)->post(route('logout'));

        $this->assertGuest();
        $response->assertRedirect(route('login'));
        $response->assertSessionHas('success');
    }

    /**
     * Uji 5: Pengguna yang belum terautentikasi (guest) dicegah mengakses rute mutasi dan sirkulasi.
     */
    public function test_unauthenticated_user_redirected_to_login_on_protected_routes(): void
    {
        // Akses form tambah buku
        $resBukuCreate = $this->get(route('buku.create'));
        $resBukuCreate->assertRedirect(route('login'));

        // Akses aksi simpan buku
        $resBukuStore = $this->post(route('buku.store'), []);
        $resBukuStore->assertRedirect(route('login'));

        // Akses sirkulasi peminjaman
        $resPeminjaman = $this->get(route('peminjaman.index'));
        $resPeminjaman->assertRedirect(route('login'));

        // Akses form catat peminjaman
        $resPinjamCreate = $this->get(route('peminjaman.create'));
        $resPinjamCreate->assertRedirect(route('login'));
    }
}
