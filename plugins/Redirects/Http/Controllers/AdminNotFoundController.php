<?php

namespace Plugins\Redirects\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Plugins\Redirects\Models\NotFoundLog;
use Plugins\Redirects\Models\RedirectRule;

class AdminNotFoundController extends Controller
{
    public function index()
    {
        if (function_exists('dbAvailable') && !dbAvailable('not_found_logs')) {
            return view('redirects::admin.not-found.index', [
                'logs' => null,
                'tableExists' => false,
            ]);
        }

        $logs = NotFoundLog::orderBy('hits', 'desc')->paginate(15);

        return view('redirects::admin.not-found.index', [
            'logs' => $logs,
            'tableExists' => true,
        ]);
    }

    public function convertToRedirect(Request $request, $id)
    {
        if (function_exists('dbAvailable') && (!dbAvailable('not_found_logs') || !dbAvailable('redirect_rules'))) {
            return redirect()->back()->with('error', 'As tabelas necessárias ainda não foram criadas.');
        }

        $log = NotFoundLog::findOrFail($id);

        $request->validate([
            'new_url' => 'required|string',
        ]);

        RedirectRule::create([
            'old_url'     => $log->url,
            'new_url'     => $request->new_url,
            'status_code' => 301,
        ]);

        $log->delete();

        return redirect()->route('admin.redirects.404')->with('success', 'Erro 404 convertido em regra de redirecionamento com sucesso!');
    }

    public function destroy($id)
    {
        if (function_exists('dbAvailable') && !dbAvailable('not_found_logs')) {
            return redirect()->back()->with('error', 'A tabela de logs não existe.');
        }

        NotFoundLog::findOrFail($id)->delete();
        return redirect()->route('admin.redirects.404')->with('success', 'Log removido com sucesso!');
    }
}
