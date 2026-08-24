<?php

namespace Plugins\Asaas\Services;

use Plugins\Asaas\Models\AsaasInvoice;
use App\Support\HookManager;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Event;

class AsaasPaymentService
{
    protected AsaasApiClient $apiClient;

    public function __construct(AsaasApiClient $apiClient)
    {
        $this->apiClient = $apiClient;
    }

    /**
     * Gera o Link de Pagamento e salva o registro no Banco Cego
     */
    public function generateCheckoutLink(float $amount, string $productName, string $externalReference, string $method = 'undefined'): array
    {
        $linkData = $this->apiClient->createPaymentLink($amount, $productName, $externalReference, $method);

        Log::info('[ASAAS SERVICE] Salvando AsaasInvoice localmente.', [
            'payment_id_salvo' => $linkData['id'],
            'external_reference' => $externalReference
        ]);

        // Salva na tabela local com ZERO dados pessoais
        $invoice = AsaasInvoice::create([
            'payment_id'         => $linkData['id'],
            'external_reference' => $externalReference,
            'payment_method'     => $method,
            'amount'             => $amount,
            'status'             => 'pending',
            'invoice_url'        => $linkData['url'],
        ]);

        return [
            'success'     => true,
            'invoice_id'  => $invoice->id,
            'payment_id'  => $linkData['id'],
            'invoice_url' => $linkData['url'],
        ];
    }

    /**
     * Confirma o pagamento vindo do Webhook e avisa os outros módulos via Hook
     */
    public function confirmPayment(
        string $paymentId,
        string $status,
        ?string $customerId = null,
        ?string $paymentLinkId = null,
        ?string $externalReference = 'external_charge',
        ?float $amount = 0,
        ?string $paymentMethod = 'undefined',
        ?string $invoiceUrl = null
    ): ?AsaasInvoice {
        Log::info('[ASAAS SERVICE] Iniciando confirmPayment.', [
            'procurando_paymentId' => $paymentId,
            'procurando_paymentLinkId' => $paymentLinkId,
            'novo_status' => $status
        ]);

        // 1. Tenta buscar pelo ID real de transação
        $invoice = AsaasInvoice::where('payment_id', $paymentId)->first();

        // 2. Se não achou e temos o ID do Link, busca por ele
        if (!$invoice && $paymentLinkId) {
            Log::info('[ASAAS SERVICE] Fatura não localizada pelo ID real de transação. Buscando pelo ID do Link de Pagamento.', [
                'paymentLinkId' => $paymentLinkId
            ]);

            $invoice = AsaasInvoice::where('payment_id', $paymentLinkId)->first();

            if ($invoice) {
                Log::info('[ASAAS SERVICE] Fatura localizada pelo Link de Pagamento. Atualizando o registro para o ID real de transação.', [
                    'id_local_antigo' => $invoice->payment_id,
                    'id_real_novo' => $paymentId
                ]);

                $invoice->payment_id = $paymentId;
                $invoice->save();
            }
        }

        // 3. AUTO-RECUPERAÇÃO: Se a fatura pendente não existe localmente,
        // reconstrói a fatura com os dados do webhook, incluindo a URL e a data de pagamento corretas!
        if (!$invoice) {
            Log::warning('[ASAAS SERVICE] Fatura não localizada no banco local de produção. Ativando mecanismo de auto-recuperação.', [
                'paymentId' => $paymentId,
                'external_reference' => $externalReference,
                'amount' => $amount
            ]);

            $invoice = AsaasInvoice::create([
                'payment_id'         => $paymentId,
                'customer_id'        => $customerId,
                'external_reference' => $externalReference ?? 'external_charge',
                'payment_method'     => $paymentMethod ?? 'undefined',
                'amount'             => $amount ?? 0,
                'status'             => $status,
                'invoice_url'        => $invoiceUrl, // <-- GRAVA O LINK DA FATURA NA AUTO-RECUPERAÇÃO
                'paid_at'            => $status === 'paid' ? now() : null, // <-- GRAVA A DATA DE PAGAMENTO
            ]);
        }

        $updateData = ['status' => $status];
        if ($customerId) {
            $updateData['customer_id'] = $customerId;
        }

        // Se a fatura já existia como pendente, grava a data de pagamento
        if ($status === 'paid' && (is_null($invoice->paid_at) || !$invoice->isPaid())) {
            $updateData['paid_at'] = now();
        }

        $invoice->update($updateData);

        Log::info('[ASAAS SERVICE] Fatura local atualizada.', [
            'id_interno_fatura' => $invoice->id,
            'novo_status_salvo' => $invoice->status,
            'external_reference' => $invoice->external_reference
        ]);

        // 💡 DISPARA O HOOK DO LUNAR BASE PARA O PRODUTO ENTREGAR O CONTEÚDO!
        if ($status === 'paid') {
            Log::info('[ASAAS SERVICE] Disparando evento asaas.payment_approved para os ouvintes.', [
                'external_reference' => $invoice->external_reference
            ]);

            Event::dispatch('asaas.payment_approved', [
                'invoice'            => $invoice,
                'external_reference' => $invoice->external_reference,
                'customer_id'        => $customerId,
            ]);
        }

        return $invoice;
    }
}
