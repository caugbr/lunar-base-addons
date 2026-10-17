<?php

namespace Plugins\FrontendLogin;

use Illuminate\Support\ServiceProvider;
use App\Helpers\ContentHelper;

class FrontendLoginServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->loadRoutesFrom(__DIR__ . '/routes.php');
    }

    public function boot(): void
    {
        $this->loadViewsFrom(__DIR__ . '/resources/views', 'frontend-login');

        ContentHelper::registerShortcode(
            'login_form',
            function ($attributes, $content) {
                $redirect = $attributes['redirect'] ?? 'current';
                $view = $attributes['view'] ?? 'box';
                return frontend_login_form($redirect, $view);
            },
            'Exibe formulário de login no front-end',
            '[login_form redirect="current"]',
            [
                'redirect' => [
                    'label'       => 'Destino após o login (current ou admin)',
                    'type'        => 'text',
                    'placeholder' => 'current',
                ],
            ]
        );
    }
}
