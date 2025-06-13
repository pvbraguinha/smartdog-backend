<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;
use App\Services\PetTransformationService;

class PetHumanController extends Controller
{
    protected $transformationService;

    public function __construct(PetTransformationService $transformationService)
    {
        $this->transformationService = $transformationService;
    }

    public function upload(Request $request)
    {
        try {
            $arquivos = $request->allFiles();
            $session = $request->input('session');
            $breed = $request->input('breed');

            Log::info('🧪 Arquivos recebidos:', array_keys($arquivos));

            $pastas = [
                'focinho' => 'focinho',
                'frontal' => 'frontal',
                'angulo'  => 'angulo',
            ];

            $paths = [];
            $urls = [];
            $errors = [];

            // Salva todas as imagens enviadas no S3
            foreach ($pastas as $tipo => $pasta) {
                if (!isset($arquivos[$tipo])) {
                    continue;
                }

                $file = $arquivos[$tipo];
                if (!$file->isValid()) {
                    $errors[$tipo] = 'Arquivo inválido.';
                    continue;
                }

                $diretorio = "uploads/meupethumano/{$pasta}";
                $path = Storage::disk('s3')->putFile($diretorio, $file);
                $paths[$tipo] = $path;
                $urls[$tipo] = Storage::disk('s3')->url($path);
                Log::info("✔️ {$tipo} salvo em: {$path}");
            }

            // "frontal" é obrigatória para processar no Replicate
            if (empty($urls['frontal'])) {
                return response()->json([
                    'success' => false,
                    'error' => 'Imagem frontal é obrigatória.'
                ], 400);
            }

            // Só envia a frontal como imagem de controle pro service
            $urlsControle = [
                'frontal' => $urls['frontal'],
                // Focinho e angulo são armazenados no S3 e podem ser usados no futuro, mas não enviados pro Replicate agora
            ];

            $result = $this->transformationService->transformPet($urlsControle, $session, $breed);

            // Junta as URLs de todas as imagens salvas ao resultado
            $result['uploaded_images'] = $urls;

            return response()->json($result);
        } catch (\Exception $e) {
            Log::error("❌ Erro geral no upload/transformação: " . $e->getMessage(), [
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
