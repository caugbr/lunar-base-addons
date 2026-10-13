<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('visual_snippets', function (Blueprint $table) {
            $table->bigIncrements('id');

            // Identificação única para uso em shortcodes: [vs-template slug="card-depoimento"]
            $table->string('slug')->unique();
            $table->string('title');
            $table->string('description')->nullable();

            // Árvore JSON do Tiptap para quando abrirmos o editor de novo na edição do molde
            $table->json('content_json')->nullable();

            // HTML gerado com as marcações data-var="..." para mesclagem rápida
            $table->longText('content');

            // Metadados dos slots mapeados (quais campos existem neste molde)
            // Ex: [{"key": "titulo", "label": "Título", "type": "text"}, {"key": "foto", "label": "Foto", "type": "image"}]
            $table->json('payload')->nullable();

            // Preview visual para a galeria (gerado por print canvas ou placeholder)
            $table->string('thumbnail_url')->nullable();

            // Controle de ativação
            $table->boolean('is_active')->default(true);

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('visual_snippets');
    }
};
