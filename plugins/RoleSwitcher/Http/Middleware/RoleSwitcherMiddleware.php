<?php

namespace Plugins\RoleSwitcher\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class RoleSwitcherMiddleware
{
    public function handle(Request $request, Closure $next)
    {
        if (auth()->check() && session()->has('role_switcher_active')) {
            $switchedRole = session('role_switcher_active');
            $user = auth()->user();

            // Aplica o papel simulado em memória
            $user->setAttribute('role', $switchedRole);

            // Finge que o original em memória é o switchedRole para evitar flags sujas (isDirty)
            // mas sem afetar o banco graças ao hook User::saving que criamos
            $user->syncOriginalAttribute('role');

            unset($user->permissions);
        }

        return $next($request);
    }
}
