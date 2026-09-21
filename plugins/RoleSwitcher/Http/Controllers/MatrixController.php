<?php

namespace Plugins\RoleSwitcher\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class MatrixController extends Controller
{
    public function index()
    {
        // Lê todos os papéis disponíveis no sistema (core + plugins)
        $roles = config('rolesPermissions.roles', []);

        // Lê a matriz de permissões
        $matrix = getOption('role_switcher_matrix', []);
        if (is_string($matrix)) {
            $matrix = json_decode($matrix, true) ?? [];
        }

        // Lê a configuração de redirecionamentos personalizados
        $redirects = getOption('role_switcher_redirects', []);
        if (is_string($redirects)) {
            $redirects = json_decode($redirects, true) ?? [];
        }

        // Coleta apenas os papéis que foram marcados como destino em pelo menos um papel
        $activeTargetRoles = collect($matrix)
            ->flatten()
            ->unique()
            ->filter(fn ($role) => isset($roles[$role]))
            ->values()
            ->all();

        return view('role-switcher::matrix', compact('roles', 'matrix', 'redirects', 'activeTargetRoles'));
    }

    public function update(Request $request)
    {
        $matrix = $request->input('matrix', []);
        $rawRedirects = $request->input('redirects', []);

        // Higieniza as URLs removendo espaços em branco e entradas vazias desnecessárias
        $redirects = [];
        foreach ($rawRedirects as $roleKey => $url) {
            $trimmedUrl = trim($url);
            if (!empty($trimmedUrl)) {
                $redirects[$roleKey] = $trimmedUrl;
            }
        }

        // Grava as opções no banco
        setOption('role_switcher_matrix', $matrix, 'json');
        setOption('role_switcher_redirects', $redirects, 'json');

        return back()->with('success', 'Matriz de papéis e redirecionamentos atualizados com sucesso!');
    }
}
