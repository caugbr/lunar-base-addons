<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tracker_page_views', function (Blueprint $table) {
            $table->string('country_code', 3)->nullable()->after('browser'); // ex: BR, US, PT
            $table->string('country_name', 100)->nullable()->after('country_code'); // ex: Brasil
            $table->string('region_name', 100)->nullable()->after('country_name'); // ex: São Paulo (Estado)
            $table->string('city_name', 100)->nullable()->after('region_name'); // ex: Campinas
        });
    }

    public function down(): void
    {
        Schema::table('tracker_page_views', function (Blueprint $table) {
            $table->dropColumn(['country_code', 'country_name', 'region_name', 'city_name']);
        });
    }
};
