<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Illuminate\View\View;

class AuthController extends Controller
{
    /**
     * Show login form.
     */
    public function showLogin(): View|RedirectResponse
    {
        if (Auth::check()) {
            return $this->redirectBasedOnRole(Auth::user());
        }

        return view('auth.login');
    }

    /**
     * Handle login request (supports email or username).
     */
    public function login(Request $request): RedirectResponse
    {
        $credentials = $request->validate([
            'login' => ['required', 'string'],
            'password' => ['required', 'string'],
        ]);

        $throttleKey = Str::transliterate(Str::lower($credentials['login']).'|'.$request->ip());

        if (RateLimiter::tooManyAttempts($throttleKey, 5)) {
            $seconds = RateLimiter::availableIn($throttleKey);
            return back()->withErrors([
                'login' => "Terlalu banyak percobaan masuk. Silakan coba lagi dalam {$seconds} detik.",
            ])->withInput($request->only('login'));
        }

        $isEmail = filter_var($credentials['login'], FILTER_VALIDATE_EMAIL);
        $field = $isEmail ? 'email' : 'username';

        $user = User::where($field, $credentials['login'])->first();

        if (! $user || ! Hash::check($credentials['password'], $user->password)) {
            RateLimiter::hit($throttleKey);

            return back()->withErrors([
                'login' => 'Email/Username atau kata sandi tidak cocok dengan data kami.',
            ])->withInput($request->only('login'));
        }

        if (! $user->is_active) {
            return back()->withErrors([
                'login' => 'Akun Anda sedang dinonaktifkan. Hubungi Manajer UMCO.',
            ]);
        }

        RateLimiter::clear($throttleKey);
        Auth::login($user, $request->boolean('remember'));
        $request->session()->regenerate();

        return $this->redirectBasedOnRole($user);
    }

    /**
     * Fast switch user using 6-digit PIN (DESIGN.md Section 3 & DECISIONS.md D-007).
     */
    public function switchUser(Request $request): RedirectResponse
    {
        $request->validate([
            'user_id' => ['required', 'exists:users,id'],
            'pin' => ['required', 'string'],
        ]);

        $targetUser = User::findOrFail($request->user_id);

        if (! $targetUser->is_active) {
            return back()->with('error', 'Pengguna sedang tidak aktif.');
        }

        if (! Hash::check($request->pin, $targetUser->pin_hash)) {
            return back()->with('error', 'PIN tidak sesuai. Silakan coba lagi.');
        }

        Auth::login($targetUser);
        $request->session()->regenerate();

        return $this->redirectBasedOnRole($targetUser)->with('success', "Beralih ke {$targetUser->name}");
    }

    /**
     * Handle logout.
     */
    public function logout(Request $request): RedirectResponse
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login');
    }

    /**
     * Helper to redirect user to their primary workspace.
     */
    protected function redirectBasedOnRole(User $user): RedirectResponse
    {
        if ($user->hasRole(['cashier', 'manager', 'bumk_staff'])) {
            return redirect()->route('pos.index');
        }

        if ($user->hasRole('barista')) {
            return redirect()->route('station.bar');
        }

        if ($user->hasRole('kitchen')) {
            return redirect()->route('station.kitchen');
        }

        return redirect()->route('pos.index');
    }
}
