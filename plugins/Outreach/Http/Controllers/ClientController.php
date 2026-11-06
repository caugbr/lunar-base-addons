<?php

namespace Plugins\Outreach\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Plugins\Outreach\Models\Client;
use Plugins\Outreach\Models\EmailTemplate;

class ClientController extends Controller
{
    public function index(Request $request)
    {
        if (function_exists('dbAvailable') && !dbAvailable('outreach_clients')) {
            return view('outreach::admin.clients.index', [
                'clients'     => null,
                'templates'   => collect(),
                'counts'      => [],
                'tableExists' => false,
            ]);
        }

        $search = $request->input('search');
        $status = $request->input('status');

        // 1. Query base para filtros de busca (nome, e-mail, empresa, cargo)
        $applySearch = function ($q) use ($search) {
            if ($search) {
                $q->where(function ($sub) use ($search) {
                    $sub->where('name', 'like', "%{$search}%")
                        ->orWhere('email', 'like', "%{$search}%")
                        ->orWhere('company', 'like', "%{$search}%")
                        ->orWhere('role', 'like', "%{$search}%");
                });
            }
        };

        // 2. Calcula os contadores das abas respeitando o termo de busca atual
        $countQuery = Client::query();
        $applySearch($countQuery);

        $rawStatusCounts = (clone $countQuery)
            ->selectRaw('status, count(*) as total')
            ->groupBy('status')
            ->pluck('total', 'status');

        $counts = [
            'all'         => (clone $countQuery)->count(),
            'novo'        => $rawStatusCounts['novo'] ?? 0,
            'contatado'   => $rawStatusCounts['contatado'] ?? 0,
            'em_conversa' => $rawStatusCounts['em_conversa'] ?? 0,
            'fechado'     => $rawStatusCounts['fechado'] ?? 0,
            'descartado'  => $rawStatusCounts['descartado'] ?? 0,
        ];

        // 3. Query principal dos clientes (aplica a busca E o status selecionado)
        $query = Client::withCount('notes')->latest();
        $applySearch($query);

        if ($status) {
            $query->where('status', $status);
        }

        $clients = $query->paginate(25);

        // 4. Carrega os modelos para a barra de disparo em lote
        $templates = (function_exists('dbAvailable') && dbAvailable('outreach_templates'))
            ? EmailTemplate::orderBy('name')->get()
            : collect();

        return view('outreach::admin.clients.index', [
            'clients'     => $clients,
            'templates'   => $templates,
            'counts'      => $counts,
            'tableExists' => true,
        ]);
    }

    public function create()
    {
        return view('outreach::admin.clients.create', [
            'client' => new Client()
        ]);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name'    => 'required|string|max:255',
            'email'   => 'required|email|max:255',
            'company' => 'nullable|string|max:255',
            'role'    => 'nullable|string|max:255',
            'phone'   => 'nullable|string|max:50',
            'website' => 'nullable|url|max:255',
            'status'  => 'required|in:novo,contatado,em_conversa,fechado,descartado',
            'initial_note' => 'nullable|string',
        ]);

        $client = Client::create($data);

        // Se preencheu anotação inicial na criação, grava na tabela outreach_notes
        if ($request->filled('initial_note')) {
            $client->notes()->create([
                'user_id' => auth()->id(),
                'content' => $request->initial_note,
            ]);
        }

        return redirect()->route('admin.outreach.clients.show', $client->id)
            ->with('success', 'Contato cadastrado com sucesso!');
    }

    public function show(Client $client)
    {
        $client->load(['notes.user', 'emailLogs.template']);
        $templates = EmailTemplate::orderBy('name')->get();

        return view('outreach::admin.clients.show', compact('client', 'templates'));
    }

    public function edit(Client $client)
    {
        return view('outreach::admin.clients.edit', compact('client'));
    }

    public function update(Request $request, Client $client)
    {
        $data = $request->validate([
            'name'    => 'required|string|max:255',
            'email'   => 'required|email|max:255',
            'company' => 'nullable|string|max:255',
            'role'    => 'nullable|string|max:255',
            'phone'   => 'nullable|string|max:50',
            'website' => 'nullable|url|max:255',
            'status'  => 'required|in:novo,contatado,em_conversa,fechado,descartado',
        ]);

        $client->update($data);

        return redirect()->route('admin.outreach.clients.show', $client->id)
            ->with('success', 'Contato atualizado com sucesso!');
    }

    public function destroy(Client $client)
    {
        $client->delete();
        return redirect()->route('admin.outreach.clients.index')
            ->with('success', 'Contato removido.');
    }

    public function storeNote(Request $request, Client $client)
    {
        $request->validate(['content' => 'required|string']);

        $client->notes()->create([
            'user_id' => auth()->id(),
            'content' => $request->content,
        ]);

        return back()->with('success', 'Anotação salva!');
    }

    public function destroyNote(Client $client, $noteId)
    {
        $client->notes()->where('id', $noteId)->delete();
        return back()->with('success', 'Anotação removida.');
    }
}
