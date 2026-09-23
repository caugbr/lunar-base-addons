<?php

namespace Plugins\UserSwitcher\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ImpersonateController extends Controller
{
    /**
     * Assume a identidade de outro usuário
     */
    public function start(Request $request, $userId)
    {
        $admin = auth()->user();

        // Trava de segurança: só quem gerencia usuários pode usar
        if ($admin->role !== 'admin' && !$admin->can('manage-users')) {
            abort(403, 'Ação não autorizada.');
        }

        // Não permite assumir a si mesmo
        if ((int) $admin->id === (int) $userId) {
            return back()->with('warn', 'Você já está logado na sua própria conta.');
        }

        // Impede cascata (assumir outro usuário enquanto já está simulando)
        if (session()->has('impersonate_original_admin_id')) {
            return back()->with('error', 'Você já está operando sob uma simulação. Saia dela antes de assumir outro usuário.');
        }

        $targetUser = User::findOrFail($userId);

        // Salva quem é o Administrador original na sessão antes de trocar
        session(['impersonate_original_admin_id' => $admin->id]);

        // Registra nos logs da administração
        if (function_exists('log_admin')) {
            log_admin('UserSwitch: assumiu usuário', 'users', [
                'target_user_id'   => $targetUser->id,
                'target_user_name' => $targetUser->name,
            ]);
        }

        // Efetua o login como o novo usuário
        Auth::loginUsingId($targetUser->id);

        return redirect('/admin/dashboard')->with('success', "Você agora está operando como: {$targetUser->name}.");
    }

    /**
     * Retorna à conta do Administrador original
     */
    public function stop()
    {
        if (!session()->has('impersonate_original_admin_id')) {
            return redirect('/admin/dashboard');
        }

        $originalAdminId = session('impersonate_original_admin_id');
        $impersonatedUser = auth()->user();

        // Limpa a trava de simulação
        session()->forget('impersonate_original_admin_id');

        // Retorna para a conta original do Administrador
        Auth::loginUsingId($originalAdminId);

        if (function_exists('log_admin')) {
            log_admin('UserSwitch: retornou ao admin original', 'users', [
                'exited_from_id' => $impersonatedUser?->id,
            ]);
        }

        return redirect()->route('admin.users.index')
            ->with('success', 'Você retornou à sua conta de Administrador.');
    }
}
