<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use GuzzleHttp\Client;
use GuzzleHttp\Promise;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Config;

class SnoutCompareController extends Controller
{
    public function compare(Request $request)
    {
        // 1) Valida e faz upload temporário do focinho enviado pelo usuário
        $request->validate([
            'image' => 'required|image|max:5120',
        ]);

        $tempFilename = 'focinhos-smartdog/tmp/' 
            . Str::random(20) 
            . '.' 
            . $request->file('image')->getClientOriginalExtension();

        Storage::disk('s3')->put(
            $tempFilename,
            file_get_contents($request->file('image')->getRealPath()),
            'public'
        );

        // Geramos uma URL pública (pode ser temporária se preferir)
        $uploadedUrl = Storage::disk('s3')->url($tempFilename);

        // Carrega o conteúdo em memória para enviar ao comparador
        $uploadedContents = file_get_contents($uploadedUrl);

        // 2) Lista todas as focinhos no bucket
        $paths = Storage::disk('s3')->allFiles('focinhos-smartdog');

        if (empty($paths)) {
            return response()->json([
                'recognized' => false,
                'message' => 'Não há imagens de referência cadastradas.',
            ], 404);
        }

        // 3) Monta requisições assíncronas
        $client    = new Client(['timeout' => 15]);
        $threshold = 60;
        $promises  = [];

        foreach ($paths as $path) {
            // Baixa do S3 diretamente
            $refContents = Storage::disk('s3')->get($path);

            // Cria um arquivo temporário em disco
            $refTemp = tmpfile();
            $metaRef = stream_get_meta_data($refTemp);
            file_put_contents($metaRef['uri'], $refContents);

            // Dispara a chamada assíncrona
            $promises[$path] = $client->postAsync(
                'https://api-cn.faceplusplus.com/imagepp/v2/dognosecompare',
                [
                    'multipart' => [
                        ['name' => 'api_key',        'contents' => Config::get('services.megvii.key')],
                        ['name' => 'api_secret',     'contents' => Config::get('services.megvii.secret')],
                        ['name' => 'image_file',     'contents' => $uploadedContents, 'filename' => basename($tempFilename)],
                        ['name' => 'image_ref_file', 'contents' => fopen($metaRef['uri'], 'r'), 'filename' => basename($path)],
                    ],
                ]
            );
        }

        // 4) Aguarda todas as respostas
        $results = Promise\Utils::settle($promises)->wait();

        // 5) Verifica qual correspondência passou do threshold
        foreach ($results as $path => $result) {
            if ($result['state'] === 'fulfilled') {
                $data = json_decode($result['value']->getBody(), true);
                $conf = $data['confidence'] ?? null;

                Log::info("🔍 Comparação {$path} - confidence: {$conf}");

                if ($conf !== null && $conf >= $threshold) {
                    return response()->json([
                        'recognized'    => true,
                        'reference_path'=> $path,
                        'confidence'    => $conf,
                        'threshold'     => $threshold,
                        'uploaded_url'  => $uploadedUrl,
                        'message'       => 'Focinho reconhecido com sucesso.',
                    ]);
                }
            } else {
                Log::warning("⚠️ Falha ao comparar {$path}: " . $result['reason']);
            }
        }

        // Se ninguém passou do threshold
        Log::info("📉 Nenhuma correspondência acima de {$threshold}");

        return response()->json([
            'recognized'    => false,
            'message'       => 'Focinho não reconhecido em nenhuma imagem de referência.',
            'uploaded_url'  => $uploadedUrl,
        ]);
    }
}
