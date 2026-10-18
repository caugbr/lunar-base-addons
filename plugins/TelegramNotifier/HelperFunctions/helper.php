<?php

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

if (!function_exists('telegramNotify')) {
    /**
     * Dispara uma notificação para o Telegram via Bot API.
     *
     * @param string $message Mensagem formatada (HTML por padrão)
     * @param string|null $chatId Chat ID alternativo (se nulo, usa o configurado nas options)
     * @param string $parseMode 'HTML' ou 'Markdown'
     * @return bool Retorna true se a mensagem foi aceita pelo Telegram
     */
    function telegramNotify(string $message, ?string $chatId = null, string $parseMode = 'HTML'): bool
    {
        $token = getOption('telegram_bot_token');
        $destChatId = $chatId ?: getOption('telegram_chat_id');

        if (empty($token) || empty($destChatId)) {
            Log::warning('Telegram Notifier: Disparo abortado. Token ou Chat ID não configurados.');
            return false;
        }

        try {
            $response = Http::timeout(5)->asJson()->post("https://api.telegram.org/bot{$token}/sendMessage", [
                'chat_id'                  => $destChatId,
                'text'                     => $message,
                'parse_mode'               => $parseMode,
                'disable_web_page_preview' => true,
            ]);

            if (!$response->successful()) {
                Log::error('Telegram Notifier: Erro na resposta da API', [
                    'status' => $response->status(),
                    'body'   => $response->json(),
                ]);
                return false;
            }

            return true;
        } catch (\Throwable $e) {
            Log::error('Telegram Notifier: Exceção ao enviar notificação: ' . $e->getMessage());
            return false;
        }
    }
}
