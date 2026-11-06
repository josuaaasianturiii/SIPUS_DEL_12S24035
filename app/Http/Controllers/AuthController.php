<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cookie;

class AuthController extends Controller
{
    /**
     * Tampilkan formulir login pustakawan.
     */
    public function showLoginForm(Request $request)
    {
        $rememberedEmail = $request->cookie('del_last_user_email', '');
        return view('auth.login', compact('rememberedEmail'));
    }

    /**
     * Proses autentikasi login pustakawan dengan regenerasi sesi.
     */
    public function login(Request $request)
    {
        $credentials = $request->validate([
            'email' => 'required|email',
            'password' => 'required|string',
        ]);

        if (Auth::attempt($credentials, $request->boolean('remember'))) {
            // WAJIB: Cegah serangan Session Fixation dengan regenerasi Session ID
            $request->session()->regenerate();

            // Simpan timestamp sesi aktif pengguna
            session(['login_timestamp' => now()->format('d M Y, H:i:s') . ' WIB']);

            // Simpan email terakhir di HTTP Cookie selama 7 hari (10080 menit)
            Cookie::queue('del_last_user_email', $request->email, 10080);

            return redirect()->intended(route('buku.index'))
                ->with('success', 'Sesi Anda telah aktif. Selamat datang, ' . Auth::user()->name . '!');
        }

        return back()
            ->withInput($request->only('email', 'remember'))
            ->with('error', 'Kredensial login tidak valid. Pastikan email dan password Anda benar.');
    }

    /**
     * Proses logout pustakawan dengan pemusnahan sesi aman.
     */
    public function logout(Request $request)
    {
        Auth::logout();

        // Invalidate session dan regenerate CSRF token untuk memusnahkan sesi server
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login')
            ->with('success', 'Sesi server telah dihapus dengan aman.');
    }
}
