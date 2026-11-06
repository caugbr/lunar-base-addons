<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('outreach_clients', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('email')->index();
            $table->string('company')->nullable();
            $table->string('role')->nullable();      // Cargo (ex: Diretor, Comprador)
            $table->string('phone')->nullable();
            $table->string('website')->nullable();
            $table->string('status')->default('novo'); // novo, contatado, respondido, reuniao, fechado, descartado
            $table->json('tags')->nullable();
            $table->json('custom_fields')->nullable(); // Campos flexíveis adicionais
            $table->timestamp('last_contacted_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('outreach_clients');
    }
};
