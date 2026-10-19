<?php

namespace Plugins\WhatsAppButton\Helpers;

use Carbon\Carbon;

class WhatsAppHelper
{
    /**
     * Decide se o componente deve ser renderizado no HTML
     */
    public static function shouldDisplay(): bool
    {
        $phone = getOption('whatsapp_phone');
        if (empty($phone)) {
            return false;
        }

        // Se o horário estiver ativo e a ação for OCULTAR quando fora do horário
        $scheduleEnabled = (bool) getOption('whatsapp_schedule_enabled', false);
        $offlineAction   = getOption('whatsapp_offline_action', 'hide'); // 'hide' ou 'disable'

        if ($scheduleEnabled && $offlineAction === 'hide') {
            return self::isOnline();
        }

        return true;
    }

    /**
     * Verifica se o momento atual está dentro da janela de atendimento
     */
    public static function isOnline(): bool
    {
        $scheduleEnabled = (bool) getOption('whatsapp_schedule_enabled', false);
        if (!$scheduleEnabled) {
            return true;
        }

        $now = Carbon::now(config('app.timezone', 'America/Sao_Paulo'));
        $dayOfWeek = (string) $now->dayOfWeek; // 0 (Domingo) a 6 (Sábado)

        $activeDays = getOption('whatsapp_schedule_days', ['1', '2', '3', '4', '5']);
        if (!is_array($activeDays)) {
            $activeDays = json_decode($activeDays, true) ?? [];
        }

        // Se hoje não for um dia ativo
        if (!in_array($dayOfWeek, $activeDays)) {
            return false;
        }

        $startTime = getOption('whatsapp_schedule_start', '09:00');
        $endTime   = getOption('whatsapp_schedule_end', '18:00');

        $currentTime = $now->format('H:i');
        return ($currentTime >= $startTime && $currentTime <= $endTime);
    }

    /**
     * Prepara os dados limpos para a view do botão
     */
    public static function getButtonData(): array
    {
        $rawPhone = getOption('whatsapp_phone', '');
        $cleanPhone = preg_replace('/\D/', '', $rawPhone);

        // Se o usuário digitou sem o DDI 55, adiciona
        if (strlen($cleanPhone) === 10 || strlen($cleanPhone) === 11) {
            $cleanPhone = '55' . $cleanPhone;
        }

        // Mensagem padrão dinâmica com placeholders
        $templateMessage = getOption('whatsapp_message', 'Olá! Gostaria de mais informações.');
        $currentUrl = url()->current();
        $finalMessage = str_replace(
            ['{url}', '{site}'],
            [$currentUrl, config('app.name', 'Site')],
            $templateMessage
        );

        $whatsappUrl = "https://wa.me/{$cleanPhone}?text=" . rawurlencode($finalMessage);

        $isOnline = self::isOnline();
        $startTime = getOption('whatsapp_schedule_start', '09:00');
        $endTime   = getOption('whatsapp_schedule_end', '18:00');

        return [
            'url'            => $whatsappUrl,
            'position'       => getOption('whatsapp_position', 'bottom-right'),
            'tooltip'        => getOption('whatsapp_tooltip', 'Fale Conosco no WhatsApp'),
            'pulse'          => (bool) getOption('whatsapp_pulse', true),
            'isOnline'       => $isOnline,
            'offlineAction'  => getOption('whatsapp_offline_action', 'hide'),
            'offlineMessage' => "Atendimento pausado (Horário: {$startTime} às {$endTime})",
        ];
    }
}
