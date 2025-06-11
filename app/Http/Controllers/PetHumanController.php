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

                // Gera URL pré-assinada válida por 15 minutos
                $urls[$tipo] = Storage::disk('s3')->temporaryUrl($path, now()->addMinutes(15));

                Log::info("✔️ {$tipo} salvo no S3 em: " . $path);
            } catch (\Exception $e) {
                Log::error("❌ Erro ao salvar {$tipo} no S3: " . $e->getMessage(), ['trace' => $e->getTraceAsString()]);

            }
        }

        return response()->json([
            'message' => 'Imagens recebidas e salvas no S3 com sucesso!',
            'paths' => $paths,              // caminhos no S3
            'temporary_urls' => $urls,      // URLs pré-assinadas
            'mock_human_image' => asset('mock/pethuman.jpg'), // imagem temporária de exemplo
        ]);
    }
}
