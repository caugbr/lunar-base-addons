<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Executa as alterações na tabela
     */
    public function up(): void
    {
        Schema::table('menu_items', function (Blueprint $table) {
            // Verifica se a coluna não existe antes de adicionar (segurança extra)
            if (!Schema::hasColumn('menu_items', 'domains')) {
                $table->text('domains')->nullable()->after('class');
            }
        });
    }

    /**
     * Reverte as alterações (Rollback)
     */
    public function down(): void
    {
        Schema::table('menu_items', function (Blueprint $table) {
            if (Schema::hasColumn('menu_items', 'domains')) {
                $table->dropColumn('domains');
            }
        });
    }
};
