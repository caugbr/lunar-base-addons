<?php

namespace Plugins\Space;

use Illuminate\Support\ServiceProvider;
use App\Support\HookManager;

class SpaceServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        HookManager::register('admin.head', function ($params) {
            $ret = $this->toggleMenu();
            if (preg_match("#admin/(posts|pages)/.+#", $params['path'])) {
                $ret .= $this->editorNoDistractions();
            }
            return $ret;
        });
    }

    private function editorNoDistractions() {
        return '<script src="' . asset('plugins/space/js/editorNoDistractions.js') . '"></script>';
    }

    private function toggleMenu() {
        return '<script src="' . asset('plugins/space/js/toggleMenu.js') . '"></script>';
    }
}
