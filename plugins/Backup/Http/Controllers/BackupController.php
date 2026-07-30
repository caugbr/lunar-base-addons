<?php

namespace Plugins\Backup\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\File;
use Plugins\Backup\Helpers\BackupHelper;
use Illuminate\Support\Str;

class BackupController extends Controller
{
    public function index()
    {
        $backups = BackupHelper::getBackupsList();
        return view('backup::admin.index', compact('backups'));
    }

    public function store(Request $request)
    {
        $options = [
            'include_db'      => $request->has('include_db'),
            'include_media'   => $request->has('include_media'),
            'include_plugins' => $request->has('include_plugins'),
            'include_themes'  => $request->has('include_themes'),
        ];

        try {
            $filename = BackupHelper::createBackup($options);
            return back()->with('success', "Backup '{$filename}' gerado com sucesso!");
        } catch (\Exception $e) {
            return back()->with('error', "Erro ao gerar backup: " . $e->getMessage());
        }
    }

    public function download(string $filename)
    {
        $path = storage_path('app/backups/' . basename($filename));

        if (!File::exists($path)) {
            return back()->with('error', 'Arquivo de backup não encontrado.');
        }

        return response()->download($path);
    }

    public function restore(Request $request, string $filename)
    {
        try {
            BackupHelper::restoreBackup(basename($filename));
            return response()->json([
                'success' => true,
                'message' => 'Sistema restaurado com sucesso!'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erro na restauração: ' . $e->getMessage()
            ], 500);
        }
    }

    public function destroy(string $filename)
    {
        $path = storage_path('app/backups/' . basename($filename));

        if (File::exists($path)) {
            File::delete($path);
            return back()->with('success', "Arquivo de backup removido com sucesso.");
        }

        return back()->with('error', "Arquivo de backup não encontrado.");
    }

    /**
     * Importa/Faz upload de um arquivo .zip de backup externo
     */
    public function import(Request $request)
    {
        $request->validate([
            'backup_file' => 'required|file|mimes:zip|max:512000', // Limite de até 500MB
        ], [
            'backup_file.required' => 'Por favor, selecione um arquivo .zip.',
            'backup_file.mimes'    => 'O arquivo deve ser do tipo .zip.',
            'backup_file.max'      => 'O arquivo não pode ser maior que 500MB.',
        ]);

        $file = $request->file('backup_file');

        // Sanitiza o nome do arquivo enviado
        $originalName = pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME);
        $cleanName    = Str::slug($originalName);
        $filename     = "imported-{$cleanName}.zip";

        $destinationPath = storage_path('app/backups');
        File::ensureDirectoryExists($destinationPath);

        // Se já existir um arquivo com o mesmo nome, adiciona timestamp
        if (File::exists($destinationPath . '/' . $filename)) {
            $filename = "imported-" . date('Y-m-d_H-i-s') . "-{$cleanName}.zip";
        }

        // Move o arquivo baixado para a pasta de backups
        $file->move($destinationPath, $filename);

        return back()->with('success', "Backup '{$filename}' importado com sucesso! Ele já está disponível na lista abaixo para restauração.");
    }
}
