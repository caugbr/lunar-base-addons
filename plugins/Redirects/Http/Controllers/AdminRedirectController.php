<?php

namespace Plugins\Redirects\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Plugins\Redirects\Models\RedirectRule;

class AdminRedirectController extends Controller
{
    public function index()
    {
        if (function_exists('dbAvailable') && !dbAvailable('redirect_rules')) {
            return view('redirects::admin.redirects.index', [
                'redirects' => null,
                'tableExists' => false,
            ]);
        }

        $redirects = RedirectRule::latest()->paginate(15);

        return view('redirects::admin.redirects.index', [
            'redirects' => $redirects,
            'tableExists' => true,
        ]);
    }

    public function store(Request $request)
    {
        if (function_exists('dbAvailable') && !dbAvailable('redirect_rules')) {
            return redirect()->back()->with('error', 'A tabela de redirecionamentos ainda não foi criada. Execute as migrações.');
        }

        $data = $request->validate([
            'old_url'     => 'required|string',
            'new_url'     => 'required|string',
            'status_code' => 'required|in:301,302',
        ]);

        RedirectRule::create($data);

        return redirect()->route('admin.redirects.index')->with('success', 'Regra de redirecionamento criada com sucesso!');
    }

    public function destroy($id)
    {
        if (function_exists('dbAvailable') && !dbAvailable('redirect_rules')) {
            return redirect()->back()->with('error', 'A tabela de redirecionamentos não existe.');
        }

        RedirectRule::findOrFail($id)->delete();
        return redirect()->route('admin.redirects.index')->with('success', 'Regra removida com sucesso!');
    }
}
