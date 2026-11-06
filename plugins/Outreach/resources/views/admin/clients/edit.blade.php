@extends('admin.layout')

@section('header_title', 'Editar Contato')
@section('header_subtitle', "Atualizando: {$client->name}")

@section('content')
<div class="admin-card">
    <div class="admin-card-header">
        <h2><x-lucide-user-pen class="lucid-icon" /> Editar Contato</h2>
        <div class="top-buttons">
            <a href="{{ route('admin.outreach.clients.show', $client->id) }}" class="admin-btn admin-btn-secondary">
                <x-lucide-eye class="lucid-icon" /> <span>Ver Perfil</span>
            </a>
            <a href="{{ route('admin.outreach.clients.index') }}" class="admin-btn admin-btn-secondary">
                <x-lucide-arrow-left class="lucid-icon" /> <span>Voltar</span>
            </a>
        </div>
    </div>

    @include('outreach::admin.clients._form', [
        'client' => $client,
        'action' => route('admin.outreach.clients.update', $client->id),
    ])
</div>
@endsection
