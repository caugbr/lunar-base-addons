<?php

namespace Plugins\RoleSwitcher\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class RoleSwitcherMiddleware
{
    public function handle(Request $request, Closure $next)
    {
        // Se houver usuário autenticado e a sessão de simulação estiver ativa
        if (auth()->check() && session()->has('role_switcher_active')) {
            $switchedRole = session('role_switcher_active');

            $user = auth()->user();

            // 1. Sobrescreve o atributo 'role' do modelo User em tempo de execução
            $user->role = $switchedRole;
            $user->setAttribute('role', $switchedRole);

            // 2. Limpa o cache interno do mutator 'permissions' se já tiver sido carregado
            unset($user->permissions);

            \Log::info('RoleSwitcherMiddleware: Role alterada em tempo de execução para: ' . $switchedRole);
            \Log::info('RoleSwitcherMiddleware: check: ', auth()->user()->permissions);
        }

        return $next($request);
    }
}
