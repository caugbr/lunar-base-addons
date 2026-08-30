<?php

namespace Plugins\MaximizeEditor;

use Illuminate\Support\ServiceProvider;
use App\Support\HookManager;

class MaximizeEditorServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        HookManager::register('admin.head', function ($params) {
            if (preg_match("#admin/(posts|pages)/.+#", $params['path'])) {
                return $this->editorNoDistractions();
            }
            return '';
        });
    }

    private function editorNoDistractions() {
        return '<script src="' . asset('plugins/space/js/editorNoDistractions.js') . '"></script>';
    }
}
