<?php

namespace Plugins\Outreach\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;
use Plugins\Outreach\Models\Client;

class OutreachMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public Client $client,
        public string $mailSubject,
        public string $mailBodyHtml
    ) {}

    // public function build()
    // {
    //     $processedBody = preg_replace_callback('/<img[^>]+src="([^">]+)"/i', function ($match) {
    //         $src = $match[1];

    //         // Converte a URL em caminho físico no disco do servidor
    //         // Ex: /storage/media/foto.png -> /caminho/do/projeto/public/storage/media/foto.png
    //         $relativePath = parse_url($src, PHP_URL_PATH);
    //         $localPath = public_path(ltrim($relativePath, '/'));

    //         if (file_exists($localPath)) {
    //             // Embutir a imagem usando a instância do SwiftMailer/SymfonyMailer do Laravel
    //             $cid = $this->embed($localPath);
    //             return str_replace($src, $cid, $match[0]);
    //         }

    //         return $match[0];
    //     }, $this->mailBodyHtml);

    //     return $this->subject($this->mailSubject)
    //         ->view('outreach::emails.outreach', [
    //             'body' => $processedBody,
    //         ]);
    // }
    public function build()
    {
        return $this->subject($this->mailSubject)
            ->view('outreach::emails.outreach', [
                'rawBody' => $this->mailBodyHtml,
            ]);
    }
}
