@extends('admin.layout')

@section('header_title', 'Novo Contato')
@section('header_subtitle', 'Cadastre um novo lead para prospecção')

@section('content')
<div class="admin-card">
    <div class="admin-card-header">
        <h2><x-lucide-user-plus class="lucid-icon" /> Novo Contato</h2>
        <div class="top-buttons">
            <a href="{{ route('admin.outreach.clients.index') }}" class="admin-btn admin-btn-secondary">
                <x-lucide-arrow-left class="lucid-icon" /> <span>Voltar</span>
            </a>
        </div>
    </div>

    @include('outreach::admin.clients._form', [
        'client' => $client,
        'action' => route('admin.outreach.clients.store'),
    ])
</div>
@endsection
