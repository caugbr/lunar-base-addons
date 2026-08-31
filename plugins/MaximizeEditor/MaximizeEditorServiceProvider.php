<?php

namespace Plugins\MaximizeEditor;

use Illuminate\Support\ServiceProvider;
use App\Support\HookManager;

class MaximizeEditorServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        HookManager::register('admin.head', function ($params) {
            if (preg_match("#admin/(posts|pages)/.+#", $params['path'])) {
                $this->editorNoDistractions();
            }
            return '';
        });
    }

    private function editorNoDistractions() {
        add_script('maximize-editor', '/plugins/maximize-editor/js/editorNoDistractions.js');
    }
}
