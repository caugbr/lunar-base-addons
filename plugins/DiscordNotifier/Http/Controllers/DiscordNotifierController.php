<?php

namespace Plugins\DiscordNotifier\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;

class DiscordNotifierController extends Controller
{
    public function index()
    {
        $webhookUrl = getOption('discord_webhook_url', '');
        $botName    = getOption('discord_bot_name', config('app.name', 'Lunar Base Alertas'));

        return view('discord-notifier::admin.index', compact('webhookUrl', 'botName'));
    }

    public function update(Request $request)
    {
        $validated = $request->validate([
            'discord_webhook_url' => 'nullable|url',
            'discord_bot_name'    => 'nullable|string|max:80',
        ]);

        setOption('discord_webhook_url', trim($validated['discord_webhook_url'] ?? ''), 'string');
        setOption('discord_bot_name', trim($validated['discord_bot_name'] ?? ''), 'string');

        return back()->with('success', 'Configurações do Discord salvas com sucesso!');
    }

    public function test(Request $request)
    {
        $url     = getOption('discord_webhook_url');
        $botName = getOption('discord_bot_name', config('app.name', 'Lunar Base Alertas'));

        if (empty($url)) {
            return back()->with('error', 'Preencha e salve a URL do Webhook antes de realizar o teste.');
        }

        $appUrl   = config('app.url', url('/'));
        $dataHora = now()->format('d/m/Y H:i:s');

        // Cria um Embed bonito de teste com borda azul (#5865F2 - cor oficial do Discord)
        $embed = [
            'title'       => 'Teste de Conexão - Discord Notifier',
            'description' => "Seu Webhook está configurado e funcionando perfeitamente no **Lunar Base**!\n\nAs notificações do seu site serão entregues instantaneamente neste canal.",
            'color'       => hexdec('5865F2'),
            'fields'      => [
                [
                    'name'   => '🌐 Site / Origem',
                    'value'  => "`{$appUrl}`",
                    'inline' => true,
                ],
                [
                    'name'   => '🕒 Data / Hora',
                    'value'  => $dataHora,
                    'inline' => true,
                ],
            ],
            'footer' => [
                'text' => 'Lunar Base System',
            ],
            'timestamp' => now()->toIso8601String(),
        ];

        try {
            $response = Http::timeout(6)->asJson()->post($url, [
                'username' => $botName,
                'embeds'   => [$embed],
            ]);

            if ($response->successful()) {
                return back()->with('success', 'Mensagem de teste enviada com sucesso ao canal do Discord!');
            }

            $erroApi = $response->json('message') ?? 'Código HTTP ' . $response->status();
            return back()->with('error', "O Discord recusou a mensagem: {$erroApi}");
        } catch (\Throwable $e) {
            return back()->with('error', 'Falha ao conectar aos servidores do Discord: ' . $e->getMessage());
        }
    }
}
