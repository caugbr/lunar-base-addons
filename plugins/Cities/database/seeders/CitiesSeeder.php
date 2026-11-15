<?php

namespace Plugins\Cities\Database\Seeders;

use Illuminate\Database\Seeder;
use Plugins\Cities\Services\CitiesImportService;

class CitiesSeeder extends Seeder
{
    public function run(): void
    {
        app(CitiesImportService::class)->import();
    }
}
