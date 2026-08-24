<?php

namespace Plugins\Asaas\Http\Controllers;

use App\Http\Controllers\Controller;
use Plugins\Asaas\Services\AsaasApiClient;
use Plugins\Asaas\Services\AsaasPaymentService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class AsaasWebhookController extends Controller
{
    /**
     * Endpoint do Webhook do Asaas (POST /api/v1/asaas/webhook)
     */
    public function handle(Request $request, AsaasPaymentService $paymentService)
    {
        $token = $request->header('asaas-access-token') ?? $request->header('asaas_access_token');

        $isSandbox = setting('general.asaas_environment', 'sandbox') === 'sandbox';
        $expectedToken = $isSandbox
            ? setting('general.asaas_sandbox_webhook_token', '')
            : setting('general.asaas_production_webhook_token', '');

        Log::info('[ASAAS WEBHOOK] Requisição recebida no endpoint do webhook.', [
            'headers' => $request->headers->all(),
            'payload_completo' => $request->all()
        ]);

        // Valida o token de segurança configurado no painel
        if (!empty($expectedToken) && $token !== $expectedToken) {
            Log::warning('[ASAAS WEBHOOK] Token de acesso inválido ou ausente.', [
                'recebido' => $token,
                'esperado' => $expectedToken
            ]);
            return response()->json(['error' => 'Token inválido'], 401);
        }

        $event   = $request->input('event');
        $payment = $request->input('payment');

        if (!$payment || empty($payment['id'])) {
            Log::warning('[ASAAS WEBHOOK] Payload não contém dados de pagamento válidos.');
            return response()->json(['error' => 'Payload inválido'], 400);
        }

        $paymentId         = $payment['id']; // ID real da transação (ex: pay_...)
        $paymentLinkId     = $payment['paymentLink'] ?? null; // ID do Link de Pagamento (ex: hash do link)
        $status            = AsaasApiClient::mapStatus($payment['status'] ?? '');
        $customerId        = $payment['customer'] ?? null;

        // Dados de Auto-Recuperação
        $externalReference = $payment['externalReference'] ?? 'external_charge';
        $amount            = (float) ($payment['value'] ?? 0);
        $paymentMethod     = strtolower($payment['billingType'] ?? 'undefined');
        $invoiceUrl        = $payment['invoiceUrl'] ?? null; // <-- CAPTURA A URL DA FATURA

        Log::info('[ASAAS WEBHOOK] Payload validado com sucesso. Direcionando para processamento.', [
            'event' => $event,
            'paymentId_real' => $paymentId,
            'paymentLinkId' => $paymentLinkId,
            'external_reference' => $externalReference,
            'status_mapeado' => $status
        ]);

        try {
            // PASSANDO A URL DA FATURA ADICIONAL PARA O SERVICE
            $paymentService->confirmPayment($paymentId, $status, $customerId, $paymentLinkId, $externalReference, $amount, $paymentMethod, $invoiceUrl);
            return response()->json(['success' => true, 'message' => 'Webhook processado']);
        } catch (\Exception $e) {
            Log::error('[ASAAS WEBHOOK] Erro excepcional durante processamento.', [
                'error' => $e->getMessage()
            ]);
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    /**
     * View simples de encerramento do Popup de sucesso
     */
    public function success()
    {
        return response("<script>if(window.opener){window.opener.postMessage('payment_success','*');}window.close();</script><h2>Pagamento Concluído! Pode fechar esta janela.</h2>");
    }
}
