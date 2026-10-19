<?php

namespace Plugins\WhatsAppButton\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class WhatsAppButtonController extends Controller
{
    public function index()
    {
        $settings = [
            'phone'            => getOption('whatsapp_phone', ''),
            'message'          => getOption('whatsapp_message', 'Olá! Gostaria de mais informações sobre o site.'),
            'position'         => getOption('whatsapp_position', 'bottom-right'),
            'tooltip'          => getOption('whatsapp_tooltip', 'Fale Conosco'),
            'pulse'            => (bool) getOption('whatsapp_pulse', true),
            'schedule_enabled' => (bool) getOption('whatsapp_schedule_enabled', false),
            'schedule_days'    => getOption('whatsapp_schedule_days', ['1', '2', '3', '4', '5']),
            'schedule_start'   => getOption('whatsapp_schedule_start', '09:00'),
            'schedule_end'     => getOption('whatsapp_schedule_end', '18:00'),
            'offline_action'   => getOption('whatsapp_offline_action', 'disable'),
        ];

        if (!is_array($settings['schedule_days'])) {
            $settings['schedule_days'] = json_decode($settings['schedule_days'], true) ?? [];
        }

        return view('whatsapp-button::admin.index', compact('settings'));
    }

    public function update(Request $request)
    {
        $validated = $request->validate([
            'whatsapp_phone'            => 'nullable|string',
            'whatsapp_message'          => 'nullable|string|max:500',
            'whatsapp_position'         => 'required|in:bottom-right,bottom-left',
            'whatsapp_tooltip'          => 'nullable|string|max:80',
            'whatsapp_pulse'            => 'nullable|boolean',
            'whatsapp_schedule_enabled' => 'nullable|boolean',
            'whatsapp_schedule_days'    => 'nullable|array',
            'whatsapp_schedule_start'   => 'nullable|string',
            'whatsapp_schedule_end'     => 'nullable|string',
            'whatsapp_offline_action'   => 'required|in:hide,disable',
        ]);

        setOption('whatsapp_phone', trim($validated['whatsapp_phone'] ?? ''), 'string');
        setOption('whatsapp_message', trim($validated['whatsapp_message'] ?? ''), 'string');
        setOption('whatsapp_position', $validated['whatsapp_position'], 'string');
        setOption('whatsapp_tooltip', trim($validated['whatsapp_tooltip'] ?? ''), 'string');
        setOption('whatsapp_pulse', $request->boolean('whatsapp_pulse'), 'json');

        // Horário de Atendimento
        setOption('whatsapp_schedule_enabled', $request->boolean('whatsapp_schedule_enabled'), 'json');
        setOption('whatsapp_schedule_days', $request->input('whatsapp_schedule_days', []), 'json');
        setOption('whatsapp_schedule_start', $validated['whatsapp_schedule_start'] ?? '09:00', 'string');
        setOption('whatsapp_schedule_end', $validated['whatsapp_schedule_end'] ?? '18:00', 'string');
        setOption('whatsapp_offline_action', $validated['whatsapp_offline_action'], 'string');

        return back()->with('success', 'Configurações do WhatsApp atualizadas com sucesso!');
    }
}
