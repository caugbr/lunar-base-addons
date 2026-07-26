<?php

namespace Plugins\QrCode\Helpers;

use BaconQrCode\Renderer\ImageRenderer;
use BaconQrCode\Renderer\Image\SvgImageBackEnd;
use BaconQrCode\Renderer\RendererStyle\RendererStyle;
use BaconQrCode\Writer;

class QrCodeHelper
{
    /**
     * Gera uma string SVG pura contendo o QR Code
     */
    public static function svg(string $content, int $size = 200, int $margin = 1): string
    {
        if (empty(trim($content))) {
            return '';
        }

        try {
            $renderer = new ImageRenderer(
                new RendererStyle($size, $margin),
                new SvgImageBackEnd()
            );

            $writer = new Writer($renderer);
            return $writer->writeString($content);
        } catch (\Throwable $e) {
            return '<!-- Erro ao gerar QR Code: ' . e($e->getMessage()) . ' -->';
        }
    }
}
