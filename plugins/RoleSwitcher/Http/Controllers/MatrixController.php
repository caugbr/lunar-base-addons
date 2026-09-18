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

        // Lê a matriz salva usando o helper global getOption
        $matrix = getOption('role_switcher_matrix', []);

        if (is_string($matrix)) {
            $matrix = json_decode($matrix, true) ?? [];
        }

        return view('role-switcher::matrix', compact('roles', 'matrix'));
    }

    public function update(Request $request)
    {
        $matrix = $request->input('matrix', []);

        // Grava na tabela settings usando o helper global setOption
        setOption('role_switcher_matrix', $matrix, 'json');

        return back()->with('success', 'Matriz de alternância de papéis atualizada com sucesso!');
    }
}
