<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('outreach_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('client_id')->constrained('outreach_clients')->cascadeOnDelete();
            $table->foreignId('template_id')->nullable()->constrained('outreach_templates')->nullOnDelete();
            $table->string('subject');
            $table->longText('body'); // Guarda o conteúdo real já parseado com os dados do cliente
            $table->timestamp('sent_at')->nullable();
            $table->string('status')->default('sent'); // queued, sent, failed
            $table->text('error_message')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('outreach_logs');
    }
};
