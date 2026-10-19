@extends('admin.layout')

@section('header_title', 'Botão WhatsApp')
@section('header_subtitle', 'Configure o botão flutuante de atendimento e janela de horários')

@once
@push('styles')
<style>
    .whatsapp-admin-form {
        display: flex;
        flex-direction: column;
        gap: 1.5rem;
    }

    .schedule-box {
        margin-top: 1rem;
        padding-top: 1.25rem;
        border-top: 1px solid var(--color-border, rgba(255, 255, 255, 0.08));
    }

    .schedule-days-group {
        display: flex;
        flex-wrap: wrap;
        gap: 1rem;
        margin-top: 0.5rem;
    }
</style>
@endpush
@endonce

@section('content')
<form method="POST" action="{{ route('admin.whatsapp-button.update') }}" class="whatsapp-admin-form">
    @csrf

    {{-- Card 1: Informações Principais --}}
    <div class="admin-card">
        <div class="admin-card-header">
            <h2><x-lucide-message-circle class="lucid-icon" /> Atendimento & Apresentação</h2>
        </div>

        <div class="form-group">
            <label for="whatsapp_phone">Número do WhatsApp *</label>
            <input type="text"
                   name="whatsapp_phone"
                   id="whatsapp_phone"
                   class="form-input"
                   value="{{ old('whatsapp_phone', $settings['phone']) }}"
                   placeholder="Ex: (11) 99999-8888 ou 11999998888"
                   required>
            <small class="form-help">Informe o número com DDD. O código do país (55) será inserido automaticamente se omitido.</small>
        </div>

        <div class="form-group">
            <label for="whatsapp_message">Mensagem Inicial Padrão</label>
            <textarea name="whatsapp_message"
                      id="whatsapp_message"
                      rows="3"
                      class="form-input"
                      placeholder="Olá! Estava navegando no site e gostaria de tirar uma dúvida.">{{ old('whatsapp_message', $settings['message']) }}</textarea>
            <small class="form-help">Variáveis disponíveis: <code>{url}</code> (link da página atual) e <code>{site}</code> (nome do site).</small>
        </div>

        <div class="admin-form-row">
            <div class="form-group">
                <label for="whatsapp_tooltip">Texto do Balãozinho (Chamada)</label>
                <input type="text"
                       name="whatsapp_tooltip"
                       id="whatsapp_tooltip"
                       class="form-input"
                       value="{{ old('whatsapp_tooltip', $settings['tooltip']) }}"
                       placeholder="Ex: Fale Conosco">
            </div>

            <div class="form-group">
                <label for="whatsapp_position">Posição na Tela</label>
                <select name="whatsapp_position" id="whatsapp_position" class="form-input">
                    <option value="bottom-right" {{ $settings['position'] === 'bottom-right' ? 'selected' : '' }}>Inferior Direito</option>
                    <option value="bottom-left" {{ $settings['position'] === 'bottom-left' ? 'selected' : '' }}>Inferior Esquerdo</option>
                </select>
            </div>
        </div>

        <div class="form-group checkbox-group">
            <label class="checkbox-label">
                <label for="whatsapp_pulse">
                    Ativar animação de pulsar (atrai a atenção do visitante com suavidade)
                </label>
                <x-switch
                    name="whatsapp_pulse"
                    id="whatsapp_pulse"
                    :checked="(bool)$settings['pulse']"
                    active="Ativar"
                    inactive="Não ativar"
                />
            </label>
        </div>

    {{-- Card 2: Horário de Atendimento --}}
        <div class="admin-card-header">
            <h3><x-lucide-clock class="lucid-icon" /> Horário de Atendimento</h3>
        </div>

        <div class="form-group checkbox-group">
            <label class="checkbox-label">
                <label for="schedule_toggle">
                    Restringir exibição/cliques a um horário comercial
                </label>
                <x-switch
                    name="whatsapp_schedule_enabled"
                    id="schedule_toggle"
                    :checked="(bool)$settings['schedule_enabled']"
                    active="Restringir"
                    inactive="Não restringir"
                    onChange="document.getElementById('schedule_fields').style.display = this.checked ? 'block' : 'none'"
                />
            </label>
        </div>

        <div id="schedule_fields" class="schedule-box" style="display: {{ $settings['schedule_enabled'] ? 'block' : 'none' }};">
            <div class="form-group">
                <label>Dias da Semana Ativos</label>
                <div class="schedule-days-group">
                    @php
                        $diasSemana = [
                            '1' => 'Segunda',
                            '2' => 'Terça',
                            '3' => 'Quarta',
                            '4' => 'Quinta',
                            '5' => 'Sexta',
                            '6' => 'Sábado',
                            '0' => 'Domingo',
                        ];
                    @endphp
                    @foreach($diasSemana as $valor => $rotulo)
                        <label class="checkbox-label">
                            <input type="checkbox"
                                   name="whatsapp_schedule_days[]"
                                   value="{{ $valor }}"
                                   {{ in_array($valor, $settings['schedule_days']) ? 'checked' : '' }}>
                            <span>{{ $rotulo }}</span>
                        </label>
                    @endforeach
                </div>
            </div>

            <div class="admin-form-row">
                <div class="form-group">
                    <label for="whatsapp_schedule_start">Horário de Início</label>
                    <input type="time"
                           name="whatsapp_schedule_start"
                           id="whatsapp_schedule_start"
                           class="form-input"
                           value="{{ old('whatsapp_schedule_start', $settings['schedule_start']) }}">
                </div>

                <div class="form-group">
                    <label for="whatsapp_schedule_end">Horário de Término</label>
                    <input type="time"
                           name="whatsapp_schedule_end"
                           id="whatsapp_schedule_end"
                           class="form-input"
                           value="{{ old('whatsapp_schedule_end', $settings['schedule_end']) }}">
                </div>
            </div>

            <div class="form-group">
                <label for="whatsapp_offline_action">Quando estiver FORA do horário:</label>
                <select name="whatsapp_offline_action" id="whatsapp_offline_action" class="form-input">
                    <option value="disable" {{ $settings['offline_action'] === 'disable' ? 'selected' : '' }}>
                        Desabilitar o botão (fica cinza com aviso de horário pausado)
                    </option>
                    <option value="hide" {{ $settings['offline_action'] === 'hide' ? 'selected' : '' }}>
                        Ocultar o botão completamente da página
                    </option>
                </select>
            </div>
        </div>

        {{-- Botão Salvar --}}
        <div class="buttons">
            <button type="submit" class="admin-btn admin-btn-primary">
                <x-lucide-save class="lucid-icon" /> Salvar Configurações
            </button>
        </div>
    </div>
</form>
@endsection
