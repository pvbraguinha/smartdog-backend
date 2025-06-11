<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;

class PetHumanController extends Controller
{
    public function upload(Request $request)
    {
        try {
            $arquivos = $request->allFiles();

            // Loga os nomes dos campos recebidos
            Log::info('🧪 Arquivos recebidos:', array_keys($arquivos));

            $tipos = ['focinho', 'frontal', 'angulo'];
            $paths = [];
            $urls = [];
            $errors = [];

            foreach ($tipos as $tipo) {
                if (!isset($arquivos[$tipo])) {
                    $errors[$tipo] = 'Campo não enviado.';
                    continue;
                }

                $file = $arquivos[$tipo];
                if (!$file->isValid()) {
                    $errors[$tipo] = 'Arquivo inválido.';
                    continue;
                }

                $diretorio = "uploads/meupethumano/{$tipo}s";
                $path = Storage::disk('s3')->putFile($diretorio, $file);
                $paths[$tipo] = $path;

                $urls[$tipo] = Storage::disk('s3')->temporaryUrl($path, now()->addMinutes(15));

                Log::info("✔️ {$tipo} salvo no S3 em: {$path}");
            }

            return response()->json([
                'status' => count($errors) > 0 ? 'parcial' : 'completo',
                'paths' => $paths,
                'temporary_urls' => $urls,
                'errors' => $errors,
                'mock_human_image' => asset('mock/pethuman.jpg'),
            ]);
        } catch (\Exception $e) {
            Log::error("❌ Erro geral no upload: " . $e->getMessage(), [
                'trace' => $e->getTraceAsString(),
            ]);
            return response()->json([
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
