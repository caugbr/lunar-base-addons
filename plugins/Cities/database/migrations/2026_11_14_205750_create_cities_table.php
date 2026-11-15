<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('cities', function (Blueprint $table) {
            $table->id();
            $table->string('city');
            $table->string('city_ascii')->index();
            $table->decimal('lat', 10, 7);
            $table->decimal('lng', 10, 7);
            $table->string('country');
            $table->string('iso2', 2)->nullable()->index();
            $table->string('iso3', 3)->nullable();
            $table->string('timezone')->nullable();
            $table->string('admin_name')->nullable();
            $table->string('capital')->nullable();
            $table->integer('population')->nullable()->index();
            $table->timestamps();

            $table->index(['city_ascii', 'population']);
            $table->index(['iso2', 'city_ascii']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('cities');
    }
};
