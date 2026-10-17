<?php

namespace Plugins\FrontendLogin\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Http;

class FrontendLoginController extends Controller
{
    public function login(Request $request)
    {
        $credentials = $request->validate([
            'email'    => 'required|email',
            'password' => 'required',
        ]);

        if (function_exists('setting') && setting('auth.use_captcha')) {
            $response = Http::asForm()->post('https://challenges.cloudflare.com/turnstile/v0/siteverify', [
                'secret'   => setting('auth.turnstile_secret_key'),
                'response' => $request->input('cf-turnstile-response'),
                'remoteip' => $request->ip(),
            ]);

            if (!$response->json('success')) {
                return back()->withErrors([
                    'turnstile' => 'Verificação de segurança falhou. Recarregue a página e tente novamente.',
                ])->withInput();
            }
        }

        if (Auth::attempt($credentials)) {
            $user = Auth::user();

            if (function_exists('setting') && setting('auth.2fa_enabled', false) && $user->hasTwoFactorEnabled()) {
                Auth::logout();
                session([
                    'mfa.user_id'    => $user->id,
                    'mfa.started_at' => now(),
                ]);
                return redirect()->route('two-factor.challenge');
            }

            $request->session()->regenerate();

            if (function_exists('logAdmin')) {
                logAdmin("Login realizado via frontend: {$user->email}", "auth");
            }

            $redirectTo = $request->input('redirect_to', 'current');

            if ($redirectTo === 'admin') {
                return redirect()->intended('/admin/dashboard');
            }

            $targetUrl = $request->input('current_url', url()->previous());
            return redirect()->to($targetUrl);
        }

        if (function_exists('logAdmin')) {
            logAdmin("Tentativa de login frontend falhou: {$credentials['email']}", "auth");
        }

        return back()->withErrors([
            'login' => 'As credenciais informadas não correspondem aos nossos registros.',
        ])->onlyInput('email');
    }

    public function logout(Request $request)
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        if ($request->filled('redirect_to')) {
            return redirect()->to($request->input('redirect_to'));
        }

        return redirect('/login');
    }
}
