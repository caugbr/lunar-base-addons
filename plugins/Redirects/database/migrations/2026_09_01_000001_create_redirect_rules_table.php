<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('redirect_rules', function (Blueprint $table) {
            $table->id();
            $table->string('old_url')->unique();
            $table->string('new_url');
            $table->integer('status_code')->default(301); // 301 ou 302
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('redirect_rules');
    }
};
