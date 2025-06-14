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
            $especie = $request->input('especie');
            $breed   = $request->input('breed');
            $sex     = $request->input('sex');
            $age     = $request->input('age');

            // Log para debug: todos os campos recebidos
            Log::info('[UPLOAD] Dados recebidos', [
                'session' => $session,
                'especie' => $especie,
                'breed'   => $breed,
                'sex'     => $sex,
                'age'     => $age,
                'arquivos' => array_keys($arquivos)
            ]);

            // Validação dos campos obrigatórios
            if (empty($especie) || empty($breed) || empty($sex) || empty($age)) {
                return response()->json([
                    'success' => false,
                    'error' => 'Preencha espécie, raça, sexo e idade do pet.'
                ], 400);
            }

            $pastas = [
                'focinho' => 'focinho',
                'frontal' => 'frontal',
                'angulo'  => 'angulo',
            ];

            $paths = [];
            $urls = [];
            $errors = [];

            // Checagem: impedir upload de fotos repetidas
            $hashes = [];
            foreach ($pastas as $tipo => $pasta) {
                if (isset($arquivos[$tipo]) && $arquivos[$tipo]->isValid()) {
                    $hashes[$tipo] = md5_file($arquivos[$tipo]->getRealPath());
                }
            }
            // Testa se algum par de fotos é igual
            if (
                (isset($hashes['frontal'], $hashes['focinho']) && $hashes['frontal'] === $hashes['focinho']) ||
                (isset($hashes['frontal'], $hashes['angulo']) && $hashes['frontal'] === $hashes['angulo']) ||
                (isset($hashes['focinho'], $hashes['angulo']) && $hashes['focinho'] === $hashes['angulo'])
            ) {
                return response()->json([
                    'success' => false,
                    'error' => 'As fotos de frontal, focinho e ângulo devem ser diferentes.'
                ], 400);
            }

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
            ];

            // Agora passa especie, breed, sex, age para o service!
            $result = $this->transformationService->transformPet(
                $urlsControle,
                $session,
                $especie,
                $breed,
                $sex,
                $age
            );

            // Junta as URLs de todas as imagens salvas ao resultado
            $result['uploaded_images'] = $urls;
            $result['especie'] = $especie;
            $result['breed']   = $breed;
            $result['sex']     = $sex;
            $result['age']     = $age;

            return response()->json($result);

        } catch (\Exception $e) {
            Log::error("❌ Erro geral no upload/transformação: " . $e->getMessage(), [
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'success' => false,
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
