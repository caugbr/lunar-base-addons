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
            'invoice_url' => $linkData['url'], // URL que seu botão de compra vai abrir!
        ];
    }

    /**
     * Confirma o pagamento vindo do Webhook e avisa os outros módulos via Hook
     */
    public function confirmPayment(string $paymentId, string $status, ?string $customerId = null): ?AsaasInvoice
    {
        $invoice = AsaasInvoice::where('payment_id', $paymentId)->first();

        if (!$invoice) {
            // Se o link foi gerado direto no Asaas, cria o registro histórico
            $invoice = AsaasInvoice::create([
                'payment_id'         => $paymentId,
                'customer_id'        => $customerId,
                'external_reference' => 'external_charge',
                'payment_method'     => 'undefined',
                'amount'             => 0,
                'status'             => $status,
            ]);
        }

        $updateData = ['status' => $status];
        if ($customerId) {
            $updateData['customer_id'] = $customerId;
        }

        if ($status === 'paid' && !$invoice->isPaid()) {
            $updateData['paid_at'] = now();
        }

        $invoice->update($updateData);

        // 💡 DISPARA O HOOK DO LUNAR BASE PARA O PRODUTO ENTREGAR O CONTEÚDO!
        if ($status === 'paid') {
            // HookManager::fire('asaas.payment_approved', [
            //     'invoice'            => $invoice,
            //     'external_reference' => $invoice->external_reference,
            //     'customer_id'        => $customerId,
            // ]);
            Event::dispatch('asaas.payment_approved', [
                'invoice'            => $invoice,
                'external_reference' => $invoice->external_reference,
                'customer_id'        => $customerId,
            ]);
        }

        return $invoice;
    }
}
