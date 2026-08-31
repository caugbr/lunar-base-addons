<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tracker_events', function (Blueprint $table) {
            $table->id();
            $table->string('event_name', 100)->index();      // ex: "Clique WhatsApp", "Inscrição Confirmada"
            $table->string('event_category', 50)->nullable(); // ex: "Conversão", "Download", "Botão"
            $table->string('path', 255)->index();            // página onde o clique ocorreu
            $table->string('visitor_hash', 64)->index();     // hash anônimo diário
            $table->timestamp('created_at')->index();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tracker_events');
    }
};
