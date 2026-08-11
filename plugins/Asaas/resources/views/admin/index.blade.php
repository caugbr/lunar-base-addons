@extends('admin.layout')

@section('header_title', 'Transações Asaas')
@section('header_subtitle', 'Histórico de cobranças e status (Banco Cego - Zero PII)')

@section('content')
<div class="admin-card">
    <div class="admin-card-header">
        <h2><x-lucide-credit-card class="lucid-icon" /> Vendas e Faturas</h2>
    </div>

    <div class="table-wrap">
        <table class="admin-table">
            <thead>
                <tr>
                    <th>Ref. Externa</th>
                    <th>ID Pagamento</th>
                    <th>Método</th>
                    <th>Valor</th>
                    <th>Status</th>
                    <th>Data</th>
                    <th>Ações</th>
                </tr>
            </thead>
            <tbody>
                @forelse($invoices as $inv)
                <tr>
                    <td><strong>{{ $inv->external_reference }}</strong></td>
                    <td><code>{{ $inv->payment_id }}</code></td>
                    <td>{{ strtoupper($inv->payment_method) }}</td>
                    <td>R$ {{ number_format($inv->amount, 2, ',', '.') }}</td>
                    <td>
                        @if($inv->status === 'paid')
                            <span class="admin-badge admin-badge-active">Pago</span>
                        @elseif($inv->status === 'pending')
                            <span class="admin-badge admin-badge-trial">Pendente</span>
                        @else
                            <span class="admin-badge admin-badge-suspended">{{ ucfirst($inv->status) }}</span>
                        @endif
                    </td>
                    <td>{{ $inv->created_at->format('d/m/Y H:i') }}</td>
                    <td class="admin-actions">
                        <a href="{{ route('admin.asaas.invoices.show', $inv->id) }}" class="admin-btn admin-btn-secondary" title="Ver no Asaas">
                            <x-lucide-eye class="lucid-icon" />
                        </a>
                        @if($inv->invoice_url)
                            <a href="{{ $inv->invoice_url }}" target="_blank" class="admin-btn admin-btn-secondary" title="Abrir Link do Checkout">
                                <x-lucide-external-link class="lucid-icon" />
                            </a>
                        @endif
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="7" class="admin-text-center admin-text-muted">Nenhuma transação registrada.</td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div style="padding: 1rem;">
        {{ $invoices->links() }}
    </div>
</div>
@endsection
