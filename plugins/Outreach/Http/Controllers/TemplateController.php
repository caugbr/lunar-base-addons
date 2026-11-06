<?php

namespace Plugins\Outreach\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Plugins\Outreach\Models\EmailTemplate;
use Plugins\Outreach\Models\Client;

class TemplateController extends Controller
{
    public function index(Request $request)
    {
        if (function_exists('dbAvailable') && !dbAvailable('outreach_templates')) {
            return view('outreach::admin.templates.index', [
                'templates' => null,
                'tableExists' => false,
            ]);
        }

        $query = EmailTemplate::withCount('logs')->latest();

        if ($search = $request->input('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                ->orWhere('subject', 'like', "%{$search}%");
            });
        }

        $templates = $query->paginate(15);

        return view('outreach::admin.templates.index', [
            'templates'   => $templates,
            'tableExists' => true,
        ]);
    }

    public function create()
    {
        return view('outreach::admin.templates.create', ['template' => new EmailTemplate()]);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name'    => 'required|string|max:255',
            'subject' => 'required|string|max:255',
            'body'    => 'required|string',
            'body_json' => 'nullable',
        ]);

        EmailTemplate::create($data);

        return redirect()->route('admin.outreach.templates.index')
            ->with('success', 'Modelo de e-mail criado com sucesso!');
    }

    public function edit(EmailTemplate $template)
    {
        return view('outreach::admin.templates.edit', compact('template'));
    }

    public function update(Request $request, EmailTemplate $template)
    {
        $data = $request->validate([
            'name'    => 'required|string|max:255',
            'subject' => 'required|string|max:255',
            'body'    => 'required|string',
            'body_json' => 'nullable',
        ]);

        $template->update($data);

        return redirect()->route('admin.outreach.templates.index')
            ->with('success', 'Modelo atualizado com sucesso!');
    }

    public function show(EmailTemplate $template)
    {
        // Cria um contato fictício para simular o preenchimento real das tags
        $sampleClient = new Client([
            'name'    => 'Carlos Eduardo Silva',
            'email'   => 'carlos@empresa-exemplo.com.br',
            'company' => 'Acme Inovações Ltda',
            'role'    => 'Diretor de Operações',
            'phone'   => '(11) 98765-4321',
            'website' => 'https://acme-exemplo.com.br',
        ]);

        // Executa o parser do template com o contato de exemplo
        $parsed = $template->parseFor($sampleClient);

        return view('outreach::admin.templates.show', compact('template', 'parsed', 'sampleClient'));
    }

    public function destroy(EmailTemplate $template)
    {
        $template->delete();
        return redirect()->route('admin.outreach.templates.index')
            ->with('success', 'Modelo excluído.');
    }
}
