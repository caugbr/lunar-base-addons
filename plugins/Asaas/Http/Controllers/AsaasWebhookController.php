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
    // public function handle(Request $request, AsaasPaymentService $paymentService)
    // {
    //     $token = $request->header('asaas-access-token');
    //     $expectedToken = setting('general.asaas_webhook_token', '');

    //     // Valida o token de segurança configurado no painel
    //     if (!empty($expectedToken) && $token !== $expectedToken) {
    //         \Log::warning('Asaas Webhook: Token de acesso inválido.');
    //         return response()->json(['error' => 'Token inválido'], 401);
    //     }

    //     $event  = $request->input('event');
    //     $payment = $request->input('payment');

    //     if (!$payment || empty($payment['id'])) {
    //         return response()->json(['error' => 'Payload inválido'], 400);
    //     }

    //     $paymentId  = $payment['id'];
    //     $status     = AsaasApiClient::mapStatus($payment['status'] ?? '');
    //     $customerId = $payment['customer'] ?? null;

    //     try {
    //         $paymentService->confirmPayment($paymentId, $status, $customerId);
    //         return response()->json(['success' => true, 'message' => 'Webhook processado']);
    //     } catch (\Exception $e) {
    //         \Log::error('Erro ao processar Webhook do Asaas: ' . $e->getMessage());
    //         return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
    //     }
    // }
public function handle(Request $request, AsaasPaymentService $paymentService)
    {
        $token = $request->header('asaas-access-token') ?? $request->header('asaas_access_token');
        $expectedToken = setting('general.asaas_webhook_token', '');

        if (!empty($expectedToken) && $token !== $expectedToken) {
            \Log::warning('[ASAAS WEBHOOK] Token de acesso inválido ou ausente.', [
                'recebido' => $token,
                'esperado' => $expectedToken
            ]);
            return response()->json(['error' => 'Token inválido'], 401);
        }

        $event   = $request->input('event');
        $payment = $request->input('payment');

        if (!$payment || empty($payment['id'])) {
            \Log::warning('[ASAAS WEBHOOK] Payload não contém dados de pagamento válidos.');
            return response()->json(['error' => 'Payload inválido'], 400);
        }

        $paymentId     = $payment['id']; // ID real da transação (ex: pay_...)
        $paymentLinkId = $payment['paymentLink'] ?? null; // ID do Link de Pagamento (ex: edpnvrzbywygegdz)
        $status        = AsaasApiClient::mapStatus($payment['status'] ?? '');
        $customerId    = $payment['customer'] ?? null;

        try {
            $paymentService->confirmPayment($paymentId, $status, $customerId, $paymentLinkId);
            return response()->json(['success' => true, 'message' => 'Webhook processado']);
        } catch (\Exception $e) {
            \Log::error('[ASAAS WEBHOOK] Erro excepcional durante processamento.', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
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
