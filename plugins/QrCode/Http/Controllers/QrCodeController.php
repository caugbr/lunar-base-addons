<?php

namespace Plugins\QrCode\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Plugins\QrCode\Helpers\QrCodeHelper;

class QrCodeController extends Controller
{
    /**
     * Tela principal do Gerador de QR Code livre
     */
    public function index()
    {
        return view('qrcode::admin.index');
    }

    /**
     * Força o download do arquivo em formato SVG
     */
    public function download(Request $request)
    {
        $validated = $request->validate([
            'content'  => 'required|string|max:2000',
            'size'     => 'nullable|integer|min:100|max:1000',
            'filename' => 'nullable|string|max:255',
        ]);

        $content  = $validated['content'];
        $size     = (int) ($validated['size'] ?? 300);
        $rawName  = $validated['filename'] ?? 'qrcode';
        $filename = Str::slug($rawName) ?: 'qrcode';

        $svgContent = QrCodeHelper::svg($content, $size);

        return response($svgContent, 200, [
            'Content-Type'        => 'image/svg+xml',
            'Content-Disposition' => 'attachment; filename="' . $filename . '.svg"',
        ]);
    }
}
