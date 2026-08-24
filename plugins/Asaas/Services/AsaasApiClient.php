<?php

namespace Plugins\Asaas\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Exception;

class AsaasApiClient
{
    protected string $apiKey;
    protected string $baseUrl;

    public function __construct()
    {
        $isSandbox = setting('general.asaas_environment', 'sandbox') === 'sandbox';

        $this->apiKey = $isSandbox
            ? setting('general.asaas_sandbox_api_key', '')
            : setting('general.asaas_production_api_key', '');

        $this->baseUrl = $isSandbox
            ? 'https://sandbox.asaas.com/api/v3'
            : 'https://www.asaas.com/api/v3';

        if (empty($this->apiKey)) {
            Log::warning('AsaasApiClient: Chave de API não configurada.');
        }
    }

    /**
     * Cria um Link de Pagamento / Checkout Hospedado no Asaas
     */
    public function createPaymentLink(float $amount, string $productName, string $externalReference, string $method = 'UNDEFINED'): array
    {
        $billingType = match($method) {
            'pix'         => 'PIX',
            'credit_card' => 'CREDIT_CARD',
            'boleto'      => 'BOLETO',
            default       => 'UNDEFINED', // Permite todas as opções no checkout do Asaas
        };

        $successUrl = rtrim(config('app.url'), '/') . '/api/v1/asaas/success';

        $response = Http::withHeaders([
            'access_token' => $this->apiKey,
            'User-Agent'   => 'LunarBase-Asaas/1.0',
            'Content-Type' => 'application/json',
        ])->post($this->baseUrl . '/paymentLinks', [
            'name'              => $productName,
            'billingType'       => $billingType,
            'chargeType'        => 'DETACHED',
            'value'             => $amount,
            'dueDateLimitDays'  => 3,
            'externalReference' => $externalReference,
            'notificationDisabled' => setting('general.asaas_send_notifications', true),
            'callback'          => [
                'successUrl'   => $successUrl,
                'autoRedirect' => true,
            ]
        ]);

        if (!$response->successful()) {
            Log::error('Asaas API Error ao criar Payment Link', ['response' => $response->body()]);
            throw new Exception('Erro na comunicação com o Asaas: ' . ($response->json()['errors'][0]['description'] ?? 'Erro desconhecido'));
        }

        return $response->json();
    }

    /**
     * Busca dados cadastrais do comprador ao vivo sob demanda (Sem salvar no local)
     */
    public function getCustomerDetails(string $customerId): array
    {
        if (empty($customerId)) return [];

        $response = Http::withHeaders([
            'access_token' => $this->apiKey,
            'User-Agent'   => 'LunarBase-Asaas/1.0',
        ])->get($this->baseUrl . '/customers/' . $customerId);

        return $response->successful() ? $response->json() : [];
    }

    /**
     * Mapeia o status do Asaas para o padrão simplificado do sistema
     */
    public static function mapStatus(string $asaasStatus): string
    {
        return match($asaasStatus) {
            'CONFIRMED', 'RECEIVED', 'RECEIVED_IN_CASH' => 'paid',
            'PENDING', 'AWAITING_PAYMENT'               => 'pending',
            'FAILED'                                    => 'failed',
            'REFUNDED'                                  => 'refunded',
            default                                     => 'pending'
        };
    }
}
