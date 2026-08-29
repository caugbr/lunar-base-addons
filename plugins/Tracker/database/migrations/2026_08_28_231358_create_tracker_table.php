<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tracker_page_views', function (Blueprint $table) {
            $table->id();
            $table->string('path', 255)->index();          // ex: cursos/doutrina-espirita
            $table->string('referrer_host', 255)->nullable(); // ex: google.com, instagram.com
            $table->string('device', 20)->nullable();       // mobile, desktop, tablet
            $table->string('browser', 50)->nullable();      // Chrome, Firefox, Safari
            $table->string('visitor_hash', 64)->index();   // hash anônimo (IP + Salt do dia)
            $table->timestamp('created_at')->index();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tracker_page_views');
    }
};
