<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('archived_records', function (Blueprint $table) {
            $table->id();
            $table->string('archivable_type');   // App\Models\Post ou App\Models\Page
            $table->unsignedBigInteger('original_id');
            $table->string('title');
            $table->string('slug')->nullable();
            $table->longText('payload');         // JSON completo dos atributos, metas e termos
            $table->timestamp('original_created_at')->nullable();
            $table->timestamp('purged_at');      // Data em que foi para o cofre
            $table->timestamps();

            $table->index(['archivable_type', 'original_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('archived_records');
    }
};
