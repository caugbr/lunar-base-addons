<?php

namespace Plugins\Outreach\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Mail;
use Plugins\Outreach\Mail\OutreachMail;
use Plugins\Outreach\Models\Client;
use Plugins\Outreach\Models\EmailTemplate;
use Plugins\Outreach\Models\EmailLog;

class SendOutreachEmailJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $tries = 2;

    public function __construct(
        public Client $client,
        public EmailTemplate $template,
        public int $logId
    ) {}

    public function handle(): void
    {
        $log = EmailLog::find($this->logId);

        try {
            // Renderiza o assunto e corpo com as tags do cliente
            $parsed = $this->template->parseFor($this->client);

            // Dispara via SMTP do Brevo
            Mail::to($this->client->email)->send(
                new OutreachMail($this->client, $parsed['subject'], $parsed['body'])
            );

            // Atualiza o log para sucesso
            if ($log) {
                $log->update([
                    'status'  => 'sent',
                    'sent_at' => now(),
                ]);
            }

            // Marca o contato como contatado
            $this->client->update([
                'last_contacted_at' => now(),
                'status' => $this->client->status === 'novo' ? 'contatado' : $this->client->status,
            ]);

        } catch (\Throwable $e) {
            if ($log) {
                $log->update([
                    'status'        => 'failed',
                    'error_message' => $e->getMessage(),
                ]);
            }

            throw $e;
        }
    }
}
