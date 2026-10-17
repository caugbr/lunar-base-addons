@once
<link rel="stylesheet" href="{{ asset('plugins/frontend-login/css/frontend-login-small.css') }}">
@endonce

<div class="frontend-login-wrapper small">
    @auth
        <div class="frontend-login-status">
            <a href="{{ route('admin.profile.edit') }}" class="frontend-login-link">
                <strong>{{ Auth::user()->name }}</strong>
            </a>

            <a href="{{ route('admin.dashboard.index') }}" class="frontend-login-dashboard-button" title="Dashboard">
                <x-lucide-settings class="lucid-icon" />
            </a>

            <form action="{{ route('frontend-login.logout') }}" method="POST" class="frontend-login-inline-form">
                @csrf

                @if($redirect === 'current')
                    <input type="hidden" name="redirect_to" value="{{ $currentUrl }}">
                @endif

                <button type="submit" class="frontend-login-logout-button" title="Logout">
                    <x-lucide-log-out class="lucid-icon" />
                </button>
            </form>
        </div>
    @else

        <form action="{{ route('frontend-login.submit') }}" method="POST" class="frontend-login-form">
            @csrf
            <input type="hidden" name="redirect_to" value="{{ $redirect }}">
            <input type="hidden" name="current_url" value="{{ $currentUrl }}">

            <input type="email"
                name="email"
                id="frontend_email"
                class="frontend-login-input"
                value="{{ old('email') }}"
                placeholder="E-mail"
                required
                autocomplete="email">

            <input type="password"
                name="password"
                id="frontend_password"
                class="frontend-login-input"
                placeholder="Senha"
                required
                autocomplete="current-password">

            <button type="submit" class="frontend-login-submit-button" title="Login">
                <x-lucide-log-in class="lucid-icon" />
            </button>

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
        </form>
    @endauth
</div>
