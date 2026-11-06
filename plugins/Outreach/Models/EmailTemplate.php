<?php

namespace Plugins\Outreach\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class EmailTemplate extends Model
{
    protected $table = 'outreach_templates';

    protected $fillable = ['name', 'subject', 'body', 'body_json'];

    // protected $attributes = ['body_json' => '{}'];

    protected $casts = ['body_json' => 'array'];

    public function logs(): HasMany
    {
        return $this->hasMany(EmailLog::class, 'template_id');
    }

    /**
     * Substitui os marcadores {{tag}} pelos dados reais do cliente
     */
    public function parseFor(Client $client): array
    {
        $replacements = [
            '{{nome}}'          => $client->name,
            '{{primeiro_nome}}' => $client->first_name,
            '{{empresa}}'       => $client->company ?: 'sua empresa',
            '{{cargo}}'         => $client->role ?: '',
            '{{email}}'         => $client->email,
            '{{telefone}}'      => $client->phone ?: '',
            '{{site}}'          => $client->website ?: '',
        ];

        // Suporte para tags customizadas salvas no json do cliente
        if (!empty($client->custom_fields) && is_array($client->custom_fields)) {
            foreach ($client->custom_fields as $key => $val) {
                $replacements['{{' . $key . '}}'] = (string) $val;
            }
        }

        return [
            'subject' => str_replace(array_keys($replacements), array_values($replacements), $this->subject),
            'body'    => str_replace(array_keys($replacements), array_values($replacements), $this->body),
        ];
    }
}
