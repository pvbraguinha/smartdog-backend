<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;

class PetHumanController extends Controller
{
    public function upload(Request $request)
    {
        $request->validate([
            'focinho' => 'required|image|max:5120',
            'frontal' => 'required|image|max:5120',
            'angulo'  => 'required|image|max:5120',
        ]);

        $paths = [];
        $urls  = [];

        foreach (['focinho', 'frontal', 'angulo'] as $tipo) {
            $diretorio = "uploads/meupethumano/{$tipo}s";

            try {
                $path = Storage::disk('s3')->putFile($diretorio, $request->file($tipo));
                $paths[$tipo] = $path;

                // Gera URL temporária (15 min)
                $urls[$tipo] = Storage::disk('s3')->temporaryUrl($path, now()->addMinutes(15));

                Log::info("✔️ {$tipo} salvo no S3 em: " . $path);
            } catch (\Exception $e) {
                Log::error("❌ Erro ao salvar {$tipo} no S3: " . $e->getMessage(), [
                    'trace' => $e->getTraceAsString()
                ]);
                return response()->json([
                    'error' => "Erro ao salvar {$tipo}.",
                    'exception' => $e->getMessage()
                ], 500);
            }
        }

        return response()->json([
            'message' => 'Imagens recebidas e salvas no S3 com sucesso!',
            'paths' => $paths,
            'temporary_urls' => $urls,
            'mock_human_image' => asset('mock/pethuman.jpg'),
        ]);
    }
}
