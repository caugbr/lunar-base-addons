<?php

namespace Plugins\VisualSnippets\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class VisualSnippet extends Model
{
    use HasFactory;

    protected $table = 'visual_snippets';

    // Campos permitidos para preenchimento em massa
    protected $fillable = [
        'slug',
        'title',
        'description',
        'content_json',
        'content',
        'payload',
        'thumbnail_url',
        'is_active',
    ];

    // Casts automáticos do Eloquent (JSON vira Array e Boolean vira bool)
    protected $casts = [
        'content_json' => 'array',
        'payload'      => 'array',
        'is_active'    => 'boolean',
    ];

    /**
     * Scope para filtrar apenas snippets ativos no frontend e editor.
     * Uso: VisualSnippet::active()->where('slug', $slug)->first();
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Retorna a lista de chaves de variáveis aceitas por este molde.
     * Útil para validações ou documentação dinâmica.
     *
     * @return array Ex: ['titulo', 'foto_autor', 'link_cta']
     */
    public function getVariableKeys(): array
    {
        if (empty($this->payload) || !is_array($this->payload)) {
            return [];
        }

        return collect($this->payload)->pluck('key')->filter()->values()->toArray();
    }
}
