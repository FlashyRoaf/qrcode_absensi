<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;
use App\Models\User;
use Illuminate\Support\Facades\Cookie;

class AuthenticatedSessionController extends Controller
{
    /**
     * Show the login page.
     */
    public function create(Request $request): Response
    {
        $cookieToken = $request->cookie('save_settings');

        if (!$cookieToken) {
            Cookie::queue('save_settings', 12309, 1);
        }
        
        return Inertia::render('auth/Login', [
            'canResetPassword' => Route::has('password.request'),
            'status' => $request->session()->get('status'),
        ]);
    }

    /**
     * Handle an incoming authentication request.
     */
    public function store(LoginRequest $request): RedirectResponse
    {
        $request->authenticate();

        $request->session()->regenerate();

        /**
         * @var User $user
         */
        $user = Auth::user();

        if ($user->hasRole('atasan')) {
            return redirect()->intended(route('dashboard', absolute: false));
        }
        
        $cookieToken = $request->cookie('save_settings');
        $deviceToken = $request->cookie('device_token');

        if (empty($user->device_id)) {
            $token = Str::uuid()->toString();
            $identifier = Str::random(10);
            $user->identifier = $identifier;
            $user->device_id = $token;
            $user->save();

            return redirect()->intended(route('dashboard', absolute: false))->cookie('save_settings', $token, 60 * 24 * 365)->cookie('device_token', $identifier, 60 * 24 * 365);
        }

        if ($cookieToken != $user->device_id || $deviceToken != $user->identifier) {
            Auth::guard('web')->logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            return redirect()->route('login')
                ->withErrors([
                    'email' => 'Akun ini hanya bisa diakses dari perangkat yang terdaftar. Hubungi admin untuk reset perangkat.',
                ]);
        }

        return redirect()->intended(route('dashboard', absolute: false))->cookie('save_settings', $cookieToken, 60 * 24 * 365)->cookie('device_token', $deviceToken, 60 * 24 * 365);
    }

    /**
     * Destroy an authenticated session.
     */
    public function destroy(Request $request): RedirectResponse
    {
        Auth::guard('web')->logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect('/');
    }
}
