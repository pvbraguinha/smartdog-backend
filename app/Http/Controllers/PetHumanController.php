<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;

class PetHumanController extends Controller
{
    public function upload(Request $request)
    {
        $campos = ['focinho', 'frontal', 'angulo'];
        $paths = [];
        $urls  = [];
        $erros = [];

        Log::info('🧪 Arquivos recebidos:', array_keys($request->allFiles()));

        foreach ($campos as $campo) {
            if (!$request->hasFile($campo)) {
                $erros[$campo] = 'Campo não enviado.';
                continue;
            }

            $file = $request->file($campo);

            if (!$file->isValid()) {
                $erros[$campo] = 'Arquivo inválido ou corrompido.';
                continue;
            }

            if (!in_array($file->getMimeType(), ['image/jpeg', 'image/png', 'image/webp'])) {
                $erros[$campo] = 'Formato inválido. Envie JPEG, PNG ou WEBP.';
                continue;
            }

            if ($file->getSize() > 5 * 1024 * 1024) {
                $erros[$campo] = 'Arquivo excede 5MB.';
                continue;
            }

            try {
                $diretorio = "uploads/meupethumano/{$campo}s";
                $path = Storage::disk('s3')->putFile($diretorio, $file);
                $paths[$campo] = $path;
                $urls[$campo] = Storage::disk('s3')->temporaryUrl($path, now()->addMinutes(15));
                Log::info("✔️ {$campo} salvo no S3: {$path}");
            } catch (\Exception $e) {
                $erros[$campo] = 'Erro ao salvar no S3: ' . $e->getMessage();
                Log::error("🔥 Falha ao salvar {$campo}: " . $e->getMessage());
            }
        }

        return response()->json([
            'status' => empty($erros) ? 'sucesso' : 'parcial',
            'paths' => $paths,
            'temporary_urls' => $urls,
            'errors' => $erros,
            'mock_human_image' => asset('mock/pethuman.jpg'),
        ], empty($erros) ? 200 : 207);
    }
}
