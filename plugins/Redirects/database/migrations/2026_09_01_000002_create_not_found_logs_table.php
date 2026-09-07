<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('not_found_logs', function (Blueprint $table) {
            $table->id();
            $table->string('url')->unique();
            $table->integer('hits')->default(1);
            $table->string('referrer')->nullable();
            $table->timestamp('last_accessed_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('not_found_logs');
    }
};
