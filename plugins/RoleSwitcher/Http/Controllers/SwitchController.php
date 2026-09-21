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

        // Recupera o papel real do banco de dados (ignora eventuais simulações ativas)
        $realRole = $user->getOriginal('role') ?? $user->role;

        $matrix = getOption('role_switcher_matrix', []);
        if (is_string($matrix)) {
            $matrix = json_decode($matrix, true) ?? [];
        }

        $allowedTargets = $matrix[$realRole] ?? [];

        // Trava de segurança: só permite papéis expressamente marcados na matriz
        if (!in_array($targetRole, $allowedTargets)) {
            return back()->with('error', 'Você não tem permissão para assumir este papel.');
        }

        session(['role_switcher_active' => $targetRole]);

        log_admin('RoleSwitch: papel assumido', 'user_roles', ["to_role" => $targetRole]);

        // Obtém a URL personalizada de destino ou usa o perfil da admin como fallback
        $targetUrl = $this->getRedirectUrlForRole($targetRole);

        return redirect($targetUrl)->with('success', "Você agora está operando com o papel: {$targetRole}.");
    }

    public function reset()
    {
        $user = auth()->user();
        $realRole = $user->getOriginal('role') ?? $user->role;

        session()->forget('role_switcher_active');

        log_admin('RoleSwitch: reset', 'user_roles');

        // Retorna para a URL correspondente ao papel real do usuário
        $targetUrl = $this->getRedirectUrlForRole($realRole);

        return redirect($targetUrl)->with('success', 'Você retornou ao seu papel original.');
    }

    /**
     * Resolve a URL de redirecionamento configurada para o papel
     */
    protected function getRedirectUrlForRole(string $role): string
    {
        $redirects = getOption('role_switcher_redirects', []);
        if (is_string($redirects)) {
            $redirects = json_decode($redirects, true) ?? [];
        }

        return !empty($redirects[$role]) ? $redirects[$role] : '/admin/profile';
    }
}
