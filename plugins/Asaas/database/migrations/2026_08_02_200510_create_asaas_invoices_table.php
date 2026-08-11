<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('asaas_invoices', function (Blueprint $table) {
            $table->id();

            // Identificadores externos do Asaas (Sem dados pessoais!)
            $table->string('payment_id')->unique();   // pay_xxx ou link ID
            $table->string('customer_id')->nullable(); // cus_xxx (no cofre do Asaas)
            $table->string('external_reference')->index(); // ID do seu produto/serviço (ex: calc_102)

            // Dados da cobrança
            $table->string('payment_method')->default('undefined'); // pix, credit_card, boleto, undefined
            $table->decimal('amount', 10, 2);
            $table->string('status')->default('pending')->index();  // pending, paid, failed, refunded
            $table->timestamp('paid_at')->nullable();

            // URL do Checkout Seguro do Asaas
            $table->string('invoice_url', 500)->nullable();
            $table->json('payment_data')->nullable(); // Metadados sem PII (taxas, parcelas)

            $table->timestamps();

            $table->index('created_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('asaas_invoices');
    }
};
