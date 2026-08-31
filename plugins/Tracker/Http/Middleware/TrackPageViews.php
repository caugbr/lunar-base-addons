<?php

namespace Plugins\Tracker\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Plugins\Tracker\Models\Tracker;
use Stevebauman\Location\Facades\Location;

class TrackPageViews
{
    public function handle(Request $request, Closure $next)
    {
        $response = $next($request);

        // Só rastreia se for GET, status 200, não for AJAX e não for a área Admin
        if (
            $request->isMethod('GET') &&
            $response->getStatusCode() === 200 &&
            !$request->ajax() &&
            !$request->is('admin*') &&
            !$request->is('api*')
        ) {
            $this->recordView($request);
        }

        return $response;
    }

    private function recordView(Request $request): void
    {
        $userAgent = $request->userAgent() ?? '';

        // Ignora rastreadores e bots conhecidos
        if (preg_match('/bot|crawl|spider|mediapartners|slurp/i', $userAgent)) {
            return;
        }

        // Detecta Dispositivo
        $device = 'desktop';
        if (preg_match('/(tablet|ipad|playbook)|(android(?!.*(mobi|opera mini)))/i', $userAgent)) {
            $device = 'tablet';
        } elseif (preg_match('/(up.browser|up.link|mmp|symbian|smartphone|midp|wap|phone|android|iemobile)/i', $userAgent)) {
            $device = 'mobile';
        }

        // Detecta Navegador Simples
        $browser = 'Outro';
        if (str_contains($userAgent, 'Chrome')) $browser = 'Chrome';
        elseif (str_contains($userAgent, 'Safari')) $browser = 'Safari';
        elseif (str_contains($userAgent, 'Firefox')) $browser = 'Firefox';
        elseif (str_contains($userAgent, 'Edge')) $browser = 'Edge';

        // Origem (Referrer)
        $referrerHost = null;
        if ($referrer = $request->header('referer')) {
            $host = parse_url($referrer, PHP_URL_HOST);
            if ($host && $host !== $request->getHost()) {
                $referrerHost = str_replace('www.', '', $host);
            }
        }

        // 1. Resolve Localização pelo IP (com proteção de timeout)
        $countryCode = null;
        $countryName = null;
        $regionName  = null;
        $cityName    = null;

        $ip = $request->ip();

        if ($ip !== '127.0.0.1' && $ip !== '::1' && class_exists(\Stevebauman\Location\Facades\Location::class)) {
            try {
                if ($position = \Stevebauman\Location\Facades\Location::get($ip)) {
                    $countryCode = $position->countryCode; // ex: BR
                    $countryName = $position->countryName; // ex: Brazil
                    $regionName  = $position->regionName;  // ex: São Paulo
                    $cityName    = $position->cityName;    // ex: Campinas
                }
            } catch (\Throwable $e) {
                // Se a API de GeoIP falhar ou der timeout, continua salvando o resto dos dados
            }
        }

        $visitorHash = hash('sha256', $ip . today()->toDateString() . config('app.key'));

        try {
            Tracker::create([
                'path'          => '/' . ltrim($request->path(), '/'),
                'referrer_host' => $referrerHost,
                'device'        => $device,
                'browser'       => $browser,
                'country_code'  => $countryCode,
                'country_name'  => $countryName,
                'region_name'   => $regionName,
                'city_name'     => $cityName,
                'visitor_hash'  => $visitorHash,
                'created_at'    => now(),
            ]);
        } catch (\Throwable $e) {
            // Falha silenciosa para nunca quebrar a navegação do usuário
        }
    }
}
