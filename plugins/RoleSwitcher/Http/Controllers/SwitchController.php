<?php

namespace Plugins\RoleSwitcher\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class SwitchController extends Controller
{
    public function switch(Request $request)
    {
        $request->validate([
            'target_role' => 'required|string',
        ]);

        $user = auth()->user();
        $targetRole = $request->target_role;

        // Se já estiver simulando, o papel real é o que está na sessão; senão pega do banco
        $realRole = session('role_switcher_real_role', $user->getOriginal('role') ?? $user->role);

        $matrix = getOption('role_switcher_matrix', []);
        if (is_string($matrix)) {
            $matrix = json_decode($matrix, true) ?? [];
        }

        $allowedTargets = $matrix[$realRole] ?? [];

        if (!in_array($targetRole, $allowedTargets)) {
            return back()->with('error', 'Você não tem permissão para assumir este papel.');
        }

        // SALVA O PAPEL REAL E O SIMULADO NA SESSÃO
        session([
            'role_switcher_active'    => $targetRole,
            'role_switcher_real_role' => $realRole,
        ]);

        logAdmin('RoleSwitch: papel assumido', 'user_roles', [
            "from_role" => $realRole,
            "to_role"   => $targetRole
        ]);

        $targetUrl = $this->getRedirectUrlForRole($targetRole);

        return redirect($targetUrl)->with('success', "Você agora está operando com o papel: {$targetRole}.");
    }

    public function reset()
    {
        $user = auth()->user();

        // Recupera o papel real salvo na sessão com fallback para o banco
        $realRole = session('role_switcher_real_role', $user->getOriginal('role') ?? $user->role);

        // Garante que o atributo em memória volte ao normal imediatamente
        $user->setAttribute('role', $realRole);

        // Limpa a sessão da simulação
        session()->forget(['role_switcher_active', 'role_switcher_real_role']);

        logAdmin('RoleSwitch: reset', 'user_roles', ["restored_role" => $realRole]);

        $targetUrl = $this->getRedirectUrlForRole($realRole);

        return redirect($targetUrl)->with('success', 'Você retornou ao seu papel original.');
    }

    protected function getRedirectUrlForRole(string $role): string
    {
        $redirects = getOption('role_switcher_redirects', []);
        if (is_string($redirects)) {
            $redirects = json_decode($redirects, true) ?? [];
        }

        return !empty($redirects[$role]) ? $redirects[$role] : '/admin/profile';
    }
}
