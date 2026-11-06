<?php

namespace Plugins\Outreach\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Plugins\Outreach\Models\Client;
use Plugins\Outreach\Models\EmailTemplate;
use Plugins\Outreach\Models\EmailLog;
use Plugins\Outreach\Jobs\SendOutreachEmailJob;

class DispatchController extends Controller
{
    public function preview(Request $request)
    {
        $request->validate([
            'client_ids'  => 'required|array|min:1',
            'template_id' => 'required|exists:outreach_templates,id',
        ]);

        $clients = Client::whereIn('id', $request->client_ids)->get();
        $template = EmailTemplate::findOrFail($request->template_id);

        // Gera uma pré-visualização para o primeiro contato selecionado
        $firstPreview = $clients->first() ? $template->parseFor($clients->first()) : null;

        return view('outreach::admin.dispatch.preview', compact('clients', 'template', 'firstPreview'));
    }

    public function send(Request $request)
    {
        $request->validate([
            'client_ids'  => 'required|array|min:1',
            'template_id' => 'required|exists:outreach_templates,id',
        ]);

        $clients = Client::whereIn('id', $request->client_ids)->get();
        $template = EmailTemplate::findOrFail($request->template_id);

        $delayInSeconds = 0;

        foreach ($clients as $client) {
            $parsed = $template->parseFor($client);

            // Cria o registro no histórico como enfileirado
            $log = EmailLog::create([
                'client_id'   => $client->id,
                'template_id' => $template->id,
                'subject'     => $parsed['subject'],
                'body'        => $parsed['body'],
                'status'      => 'queued',
            ]);

            // Enfileira com intervalo humanizado (entre 45 e 90 segundos entre cada envio)
            SendOutreachEmailJob::dispatch($client, $template, $log->id)
                ->delay(now()->addSeconds($delayInSeconds));

            $delayInSeconds += rand(45, 90);
        }

        return redirect()->route('admin.outreach.clients.index')
            ->with('success', count($clients) . " e-mails enfileirados com sucesso! O envio será feito gradualmente para proteger sua reputação no Brevo.");
    }
}
