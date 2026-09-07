<?php

namespace Plugins\Redirects\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Plugins\Redirects\Models\NotFoundLog;

class CaptureNotFound
{
    public function handle(Request $request, Closure $next)
    {
        $response = $next($request);

        // 💡 Verifica se a tabela existe antes de registrar logs de 404
        if ($response->getStatusCode() === 404 && !request()->is('admin/*') && !request()->is('api/*')) {
            if (function_exists('dbAvailable') && dbAvailable('not_found_logs')) {
                $url = '/' . trim($request->path(), '/');

                // Evita logar arquivos estáticos comuns
                if (!preg_match('/\.(jpg|png|css|js|ico|svg|webp)$/', $url)) {
                    $log = NotFoundLog::firstOrNew(['url' => $url]);
                    $log->referrer = $request->header('referer');
                    $log->last_accessed_at = now();
                    $log->hits = $log->exists ? $log->hits + 1 : 1;
                    $log->save();
                }
            }
        }

        return $response;
    }
}
