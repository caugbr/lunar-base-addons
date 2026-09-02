<?php

namespace Plugins\GTranslate;

use Illuminate\Support\ServiceProvider;

class GTranslateServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        $this->loadViewsFrom(__DIR__ . '/resources/views', 'gtranslate');

        if (!request()->ajax()) {
            $this->registerGoogleTranslateScripts();
        }
    }

    private function registerGoogleTranslateScripts(): void
    {
        $defaultLang = config('pluginSettings.GTranslate.defaultLanguage', 'pt');
        $includedLangs = config('pluginSettings.GTranslate.includedLanguages', 'pt,en,es,fr,de,it');

        if (function_exists('add_inline_script')) {
            $initScript = <<<JS
            window.googleTranslateElementInit = function() {
                new google.translate.TranslateElement({
                    pageLanguage: '{$defaultLang}',
                    includedLanguages: '{$includedLangs}',
                    autoDisplay: false
                }, 'google_translate_element');
            };
            JS;
            add_inline_script($initScript);
        }

        if (function_exists('add_script')) {
            add_script(
                'google-translate-api',
                'https://translate.google.com/translate_a/element.js?cb=googleTranslateElementInit',
                [],
                '1.0.0',
                true,
                true
            );
        }
    }
}
