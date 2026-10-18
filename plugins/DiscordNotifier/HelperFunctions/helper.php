<?php

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

if (!function_exists('discordNotify')) {
    /**
     * Envia uma notificação para um canal do Discord via Webhook.
     *
     * @param string $message Mensagem principal em texto (suporta Markdown do Discord)
     * @param string|null $webhookUrl URL alternativa de Webhook (se nulo, usa a padrão das options)
     * @param array $embed Dados de Embed opcional (ex: ['title' => '...', 'color' => 0x3b82f6])
     * @return bool Retorna true se o Discord aceitar a mensagem
     */
    function discordNotify(string $message, ?string $webhookUrl = null, array $embed = []): bool
    {
        $url = $webhookUrl ?: getOption('discord_webhook_url');

        if (empty($url)) {
            Log::warning('Discord Notifier: Webhook URL não configurada.');
            return false;
        }

        $botName = getOption('discord_bot_name', config('app.name', 'Lunar Base'));

        $payload = [
            'username' => $botName,
        ];

        if (!empty($embed)) {
            $payload['embeds'] = [$embed];
            if (!empty($message)) {
                $payload['content'] = $message;
            }
        } else {
            $payload['content'] = $message;
        }

        try {
            $response = Http::timeout(5)->asJson()->post($url, $payload);

            // O Discord responde HTTP 204 No Content quando o webhook é aceito com sucesso
            if (!$response->successful()) {
                Log::error('Discord Notifier: Falha no envio', [
                    'status' => $response->status(),
                    'body'   => $response->json(),
                ]);
                return false;
            }

            return true;
        } catch (\Throwable $e) {
            Log::error('Discord Notifier: Exceção ao enviar notificação: ' . $e->getMessage());
            return false;
        }
    }
}
