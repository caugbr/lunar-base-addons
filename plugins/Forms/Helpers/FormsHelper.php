<?php

use Plugins\Forms\Models\Form;

if (!function_exists('renderForm')) {
    /**
     * Renderiza um formulário a partir de seu slug.
     *
     * @param string $slug
     * @return string
     */
    function renderForm(string $slug): string
    {
        $form = Form::active()->where('slug', $slug)->first();
        return $form ? view('forms::public.embed', ['form' => $form])->render() : '';
    }
}
