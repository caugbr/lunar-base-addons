<?php

namespace Plugins\Redirects\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Plugins\Redirects\Models\RedirectRule;

class HandleRedirects
{
    public function handle(Request $request, Closure $next)
    {
        // 💡 Verifica se a tabela existe antes de consultar o banco (evita erro durante a ativação)
        if (function_exists('dbAvailable') && dbAvailable('redirect_rules')) {
            $path = '/' . trim($request->path(), '/');

            $rule = RedirectRule::where('old_url', $path)
                ->orWhere('old_url', $request->url())
                ->first();

            if ($rule) {
                return redirect()->to($rule->new_url, $rule->status_code);
            }
        }

        return $next($request);
    }
}
