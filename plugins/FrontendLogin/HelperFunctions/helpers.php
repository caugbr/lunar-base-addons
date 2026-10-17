<?php

if (!function_exists('frontend_login_form')) {
    function frontend_login_form(string $redirect = 'current', $view = 'box'): string
    {
        return view('frontend-login::form-' . $view, [
            'redirect'   => $redirect,
            'currentUrl' => request()->fullUrl(),
        ])->render();
    }
}
