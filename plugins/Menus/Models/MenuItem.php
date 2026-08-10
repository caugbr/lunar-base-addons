<?php

namespace Plugins\Menus\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class MenuItem extends Model
{
    protected $table = 'menu_items';

    protected $fillable = [
        'menu_id', 'parent_id', 'label', 'type', 'url',
        'model_type', 'model_id', 'order', 'target', 'class', 'domains'
    ];

    protected $casts = [
        'domains' => 'array',
    ];

    public function menu(): BelongsTo
    {
        return $this->belongsTo(Menu::class, 'menu_id');
    }

    public function parent(): BelongsTo
    {
        return $this->belongsTo(MenuItem::class, 'parent_id');
    }

    public function children(): HasMany
    {
        return $this->hasMany(MenuItem::class, 'parent_id')
            ->orderBy('order', 'asc');
    }

    public function model(): MorphTo
    {
        return $this->morphTo();
    }

    public function getUrlAttribute(): string
    {
        if ($this->type === 'custom') {
            return $this->url ?? '#';
        }

        if ($this->model) {
            return $this->model->url;
        }

        return '#';
    }

    public function getLabelAttribute(?string $value): string
    {
        if ($value) {
            return $value;
        }

        if ($this->model) {
            return $this->model->title ?? $this->model->name ?? '';
        }

        return '';
    }

    /**
     * RESOLVEDOR DE VISIBILIDADE POR DOMÍNIO/NAMESPACE:
     * Retorna se este item deve ser exibido no contexto atual
     */
    public function isVisibleForCurrentSite(): bool
    {
        $domains = $this->domains;

        // Se nulo ou não definido, exibe em todos os domínios por padrão
        if (empty($domains)) {
            return true;
        }

        if (is_string($domains)) {
            $domains = json_decode($domains, true) ?? array_map('trim', explode(',', $domains));
        }

        if (!is_array($domains) || in_array('*', $domains, true)) {
            return true;
        }

        $currentHost      = function_exists('currentSiteDomain') ? currentSiteDomain() : request()->getHost();
        $currentNamespace = function_exists('currentNamespace') ? currentNamespace() : 'default';
        $isExtra          = function_exists('isExtraDomain') ? isExtraDomain() : false;

        foreach ($domains as $domain) {
            $domain = trim($domain);

            // Match por host ou por namespace do site atual
            if ($domain === $currentHost || $domain === $currentNamespace) {
                return true;
            }

            // Alias para o site principal
            if (in_array($domain, ['main', 'default', 'primary'], true) && !$isExtra) {
                return true;
            }
        }

        return false;
    }
}
