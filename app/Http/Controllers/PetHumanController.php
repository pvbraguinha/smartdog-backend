<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
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
            $petName = Str::slug($request->input('name', 'pet')); // usa 'pet' como fallback
            $date = now()->format('Y-m-d');

            Log::info('🧪 Arquivos recebidos:', array_keys($arquivos));

            $pastas = [
                'focinho' => 'focinhos',
                'frontal' => 'frontais',
                'angulo'  => 'angulos',
            ];

            $paths = [];
            $urls = [];
            $errors = [];

            foreach ($pastas as $tipo => $pasta) {
                if (!isset($arquivos[$tipo])) {
                    continue;
                }

                $file = $arquivos[$tipo];

                if (!$file->isValid()) {
                    $errors[$tipo] = 'Arquivo inválido.';
                    continue;
                }

                $ext = $file->getClientOriginalExtension();
                $random = Str::random(6);
                $filename = "{$petName}_{$tipo}_{$date}_{$random}.{$ext}";
                $diretorio = "uploads/meupethumano/{$pasta}/{$filename}";

                Storage::disk('s3')->put($diretorio, file_get_contents($file), 'public');

                $paths[$tipo] = $diretorio;
                $urls[$tipo] = Storage::disk('s3')->url($diretorio);

                Log::info("✔️ {$tipo} salvo em: {$diretorio}");
            }

            if (empty($urls['frontal'])) {
                return response()->json([
                    'success' => false,
                    'error' => 'Imagem frontal é obrigatória.'
                ], 400);
            }

            $urlsControle = [
                'frontal' => $urls['frontal'],
            ];

            $result = $this->transformationService->transformPet($urlsControle, $session, $breed);
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
