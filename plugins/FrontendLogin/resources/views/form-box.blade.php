@once
<link rel="stylesheet" href="{{ asset('plugins/frontend-login/css/frontend-login.css') }}">
@endonce

<div class="frontend-login-wrapper">
    @auth
        <div class="frontend-login-status">
            <p class="frontend-login-status-text">
                Logado como <strong>{{ Auth::user()->name }}</strong>.
                <a href="{{ url('/admin/dashboard') }}" class="frontend-login-link">Ir para o Painel</a>
                <span class="frontend-login-separator">|</span>
                <form action="{{ route('logout') }}" method="POST" class="frontend-login-inline-form">
                    @csrf
                    <button type="submit" class="frontend-login-logout-button">Sair</button>
                </form>
            </p>
        </div>
    @else
        <form action="{{ route('frontend-login.submit') }}" method="POST" class="frontend-login-form">
            @csrf

            <input type="hidden" name="redirect_to" value="{{ $redirect }}">
            <input type="hidden" name="current_url" value="{{ $currentUrl }}">

            @if($errors->has('login'))
                <div class="frontend-login-alert frontend-login-alert-danger">
                    {{ $errors->first('login') }}
                </div>
            @endif

            @if($errors->has('turnstile'))
                <div class="frontend-login-alert frontend-login-alert-danger">
                    {{ $errors->first('turnstile') }}
                </div>
            @endif

            <div class="frontend-login-group">
                <label for="frontend_email" class="frontend-login-label">E-mail</label>
                <input type="email"
                       name="email"
                       id="frontend_email"
                       class="frontend-login-input"
                       value="{{ old('email') }}"
                       required
                       autocomplete="email">
            </div>

            <div class="frontend-login-group">
                <label for="frontend_password" class="frontend-login-label">Senha</label>
                <input type="password"
                       name="password"
                       id="frontend_password"
                       class="frontend-login-input"
                       required
                       autocomplete="current-password">
            </div>

            @if(setting('auth.use_captcha'))
                @php
                    $siteKey = setting('auth.turnstile_site_key', '');
                    $appearance = setting('auth.turnstile_appearance', 'always');
                    $size = setting('auth.turnstile_size', 'normal');
                @endphp
                <div class="frontend-login-captcha-group">
                    <div class="cf-turnstile" data-sitekey="{{ $siteKey }}" data-appearance="{{ $appearance }}" data-size="{{ $size }}"></div>
                </div>
                <script src="https://challenges.cloudflare.com/turnstile/v0/api.js" async defer></script>
            @endif

            <div class="frontend-login-actions">
                <button type="submit" class="frontend-login-submit-button">
                    Entrar
                </button>
            </div>
        </form>
    @endauth
</div>
