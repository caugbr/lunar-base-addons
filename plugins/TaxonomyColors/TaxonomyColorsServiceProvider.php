<?php

namespace Plugins\TaxonomyColors;

use Illuminate\Support\ServiceProvider;
use App\Models\Term;
use App\Support\HookManager;

class TaxonomyColorsServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $helperFile = __DIR__ . '/Helpers/TaxonomyColorHelper.php';
        if (file_exists($helperFile)) {
            require_once $helperFile;
        }
    }

    public function boot(): void
    {
        HookManager::register('admin.create_term', function($params) {
            return '
            <div class="form-group">
                <label for="term_color">Cor do Termo</label>
                <div style="display: flex; gap: 8px; align-items: center;">
                    <input type="color" name="term_color" id="term_color" style="width: 55px; height: 38px; padding: 2px; border: 1px solid #cbd5e1; border-radius: 6px; cursor: pointer;" />
                    <small style="color: #666666;">Escolha a cor de destaque para este termo.</small>
                </div>
            </div>';
        }, 'Taxonomy Colors Plugin');

        HookManager::register('admin.edit_term', function($params) {
            $term = $params['term'] ?? null;
            $currentColor = $term ? $this->getTermColor($term->id) : '#000000';

            return '
            <div class="form-group">
                <label for="term_color">Cor do Termo</label>
                <div style="display: flex; gap: 8px; align-items: center;">
                    <input type="color" name="term_color" id="term_color" value="' . e($currentColor) . '" style="width: 45px; height: 38px; padding: 2px; border: 1px solid #cbd5e1; border-radius: 6px; cursor: pointer;" />
                    <small style="color: #666666;">Escolha a cor de destaque para este termo.</small>
                </div>
            </div>';
        }, 'Taxonomy Colors Plugin');

        Term::saved(function (Term $term) {
            if (request()->has('term_color')) {
                $color = request()->input('term_color');
                $this->saveTermColor($term->id, $color);
            }
        });

        Term::deleted(function (Term $term) {
            $this->removeTermColor($term->id);
        });
    }

    /**
     * Obtém a cor de um termo pelo ID
     */
    public function getTermColor(int $termId, string $default = '#000000'): string
    {
        $termColors = getOption('term_colors', []);
        return $termColors[$termId] ?? $default;
    }

    /**
     * Grava ou atualiza a cor vinculada ao ID do termo
     */
    private function saveTermColor(int $termId, string $color): void
    {
        $termColors = getOption('term_colors', []);
        $termColors[$termId] = $color;
        setOption('term_colors', $termColors);
    }

    /**
     * Remove a cor do termo excluído
     */
    private function removeTermColor(int $termId): void
    {
        $termColors = getOption('term_colors', []);
        if (isset($termColors[$termId])) {
            unset($termColors[$termId]);
            setOption('term_colors', $termColors);
        }
    }
}
