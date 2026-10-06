<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('heatmap_clicks', function (Blueprint $table) {
            $table->id();
            $table->string('url_path', 255)->index(); // Ex: /, /blog
            $table->enum('type', ['click', 'hover'])->default('click')->index(); // 👈 A NOVA COLUNA AQUI!
            $table->string('selector', 500); // Ex: #btn-comprar, a[href="/blog"]
            $table->timestamp('created_at')->useCurrent()->index();

            // Índice composto para buscas instantâneas na visualização
            $table->index(['url_path', 'type']);
        });

        Schema::create('heatmap_scrolls', function (Blueprint $table) {
            $table->id();
            $table->string('url_path', 255)->index(); // Ex: /, /blog/post-1
            $table->unsignedTinyInteger('max_percent'); // Guarda de 0 a 100% da profundidade
            $table->timestamp('created_at')->useCurrent()->index();

            $table->index(['url_path', 'created_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('heatmap_clicks');

        Schema::dropIfExists('heatmap_scrolls');
    }
};
