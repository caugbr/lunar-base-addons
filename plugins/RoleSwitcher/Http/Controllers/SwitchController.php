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

        return redirect('/admin/profile')->with('success', "Você agora está operando com o papel: {$targetRole}.");
    }

    public function reset()
    {
        session()->forget('role_switcher_active');

        return redirect('/admin/profile')->with('success', 'Você retornou ao seu papel original.');
    }
}
