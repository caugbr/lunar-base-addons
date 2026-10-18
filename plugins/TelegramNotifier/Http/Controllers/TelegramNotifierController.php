<?php

namespace Plugins\TelegramNotifier\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;

class TelegramNotifierController extends Controller
{
    public function index()
    {
        $botToken = getOption('telegram_bot_token', '');
        $chatId   = getOption('telegram_chat_id', '');

        return view('telegram-notifier::admin.index', compact('botToken', 'chatId'));
    }

    public function update(Request $request)
    {
        $validated = $request->validate([
            'telegram_bot_token' => 'nullable|string',
            'telegram_chat_id'   => 'nullable|string',
        ]);

        setOption('telegram_bot_token', trim($validated['telegram_bot_token'] ?? ''), 'string');
        setOption('telegram_chat_id', trim($validated['telegram_chat_id'] ?? ''), 'string');

        return back()->with('success', 'Configurações do Telegram salvas com sucesso!');
    }

    public function test(Request $request)
    {
        $token  = getOption('telegram_bot_token');
        $chatId = getOption('telegram_chat_id');

        if (empty($token) || empty($chatId)) {
            return back()->with('error', 'Preencha e salve o Token e o Chat ID antes de testar.');
        }

        $appUrl = config('app.url', url('/'));
        $dataHora = now()->format('d/m/Y H:i:s');

        $mensagem = "<b>Mensagem de Teste - Lunar Base</b>\n\n";
        $mensagem .= "Seu bot está configurado e funcionando corretamente!\n";
        $mensagem .= "• <b>Ambiente:</b> <code>{$appUrl}</code>\n";
        $mensagem .= "• <b>Data/Hora:</b> {$dataHora}\n";

        try {
            $response = Http::timeout(6)->asJson()->post("https://api.telegram.org/bot{$token}/sendMessage", [
                'chat_id'    => $chatId,
                'text'       => $mensagem,
                'parse_mode' => 'HTML',
            ]);

            if ($response->successful()) {
                return back()->with('success', 'Mensagem de teste enviada com sucesso ao Telegram!');
            }

            $erroApi = $response->json('description') ?? 'Código HTTP ' . $response->status();
            return back()->with('error', "Telegram recusou o envio: {$erroApi}");
        } catch (\Throwable $e) {
            return back()->with('error', 'Falha ao conectar com a API do Telegram: ' . $e->getMessage());
        }
    }
}
