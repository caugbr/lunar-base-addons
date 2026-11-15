<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('natal_charts', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->date('birth_date');
            $table->time('birth_time');
            $table->string('timezone')->default('America/Sao_Paulo');
            $table->string('city')->nullable();
            $table->decimal('latitude', 10, 7);
            $table->decimal('longitude', 10, 7);
            $table->string('house_system')->default('placidus');
            $table->boolean('include_minors')->default(false);

            $table->decimal('orb_general', 4, 1)->default(6.0);
            $table->decimal('orb_luminaries', 4, 1)->default(10.0);

            $table->string('status')->default('pending')->index();
            $table->text('error_message')->nullable();

            $table->string('sun_sign')->nullable()->index();
            $table->string('moon_sign')->nullable()->index();
            $table->string('ascendant_sign')->nullable()->index();

            $table->json('chart_data')->nullable();

            $table->boolean('is_public')->default(false);
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('natal_charts');
    }
};
