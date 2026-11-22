<?php

namespace Plugins\RollerPicker;

use Illuminate\Support\Facades\Blade;
use Illuminate\Support\ServiceProvider;

class RollerPickerServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        $this->loadViewsFrom(__DIR__ . '/resources/views', 'roller-picker');

        Blade::component('roller-picker::components.roller-picker', 'roller-picker');
    }
}
