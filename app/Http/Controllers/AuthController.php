<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use App\Models\User;

class AuthController extends Controller
{
    /**
     * Tampilkan halaman login
     */
    public function showLoginForm()
    {
        return view('auth.login');
    }

    /**
     * Proses login
     */
    public function login(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
            'password' => 'required',
        ]);

        $credentials = $request->only('email', 'password');

        if (Auth::attempt($credentials, $request->filled('remember'))) {
            $request->session()->regenerate();
            return redirect()->intended('/');
        }

        return back()->with('error', 'Email atau Password salah!');
    }

    /**
     * Tampilkan halaman register
     */
    public function showRegisterForm()
    {
        return view('auth.register');
    }

    /**
     * Proses registrasi
     */
    public function register(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users',
            'phone_number' => 'required|string|max:20',
            'password' => 'required|string|min:8|confirmed',
        ], [
            'password.confirmed' => 'Konfirmasi password harus sama dengan password.',
            'email.unique' => 'Email sudah terdaftar.',
            'password.min' => 'Password minimal 8 karakter.',
        ]);

        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'phone_number' => $request->phone_number,
            'password' => Hash::make($request->password),
        ]);

        Auth::login($user);

        return redirect('/')->with('success', 'Registrasi berhasil!');
    }

    /**
     * Logout
     */
    public function logout(Request $request)
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();
        
        return redirect('/login')->with('success', 'Anda telah logout.');
    }

    /**
     * Tampilkan halaman forgot password
     */
    public function showForgotPasswordForm()
    {
        return view('auth.forgot-password');
    }

    /**
     * Verifikasi email dan phone number untuk reset password
     */
    public function verifyResetPassword(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
            'phone_number' => 'required|string',
        ]);

        $user = User::where('email', $request->email)
                    ->where('phone_number', $request->phone_number)
                    ->first();

        if (!$user) {
            return back()->with('error', 'Email atau nomor telepon tidak cocok dengan data yang terdaftar.');
        }

        // Simpan email di session untuk proses reset password
        $request->session()->put('reset_email', $user->email);
        
        return redirect()->route('password.reset.form')->with('success', 'Verifikasi berhasil! Silakan masukkan password baru.');
    }

    /**
     * Tampilkan form reset password
     */
    public function showResetPasswordForm(Request $request)
    {
        if (!$request->session()->has('reset_email')) {
            return redirect()->route('password.forgot')->with('error', 'Sesi verifikasi telah habis. Silakan verifikasi ulang.');
        }

        return view('auth.reset-password');
    }

    /**
     * Proses reset password
     */
    public function resetPassword(Request $request)
    {
        if (!$request->session()->has('reset_email')) {
            return redirect()->route('password.forgot')->with('error', 'Sesi verifikasi telah habis. Silakan verifikasi ulang.');
        }

        $request->validate([
            'password' => 'required|string|min:8|confirmed',
        ], [
            'password.confirmed' => 'Konfirmasi password harus sama dengan password.',
            'password.min' => 'Password minimal 8 karakter.',
        ]);

        $user = User::where('email', $request->session()->get('reset_email'))->first();

        if (!$user) {
            return redirect()->route('password.forgot')->with('error', 'User tidak ditemukan.');
        }

        $user->password = Hash::make($request->password);
        $user->save();

        // Hapus session reset
        $request->session()->forget('reset_email');

        return redirect()->route('login')->with('success', 'Password berhasil diubah! Silakan login dengan password baru.');
    }
}
