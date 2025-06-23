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
        // 1) Valida e faz upload temporário do focinho enviado
        $request->validate([
            'image' => 'required|image|max:5120',
        ]);

        $extension = $request->file('image')->getClientOriginalExtension();
        $tempFilename = 'focinhos-smartdog/tmp/' . Str::random(20) . '.' . $extension;
        Storage::disk('s3')->put(
            $tempFilename,
            file_get_contents($request->file('image')->getRealPath()),
            'public'
        );
        $uploadedUrl      = Storage::disk('s3')->url($tempFilename);
        $uploadedContents = file_get_contents($uploadedUrl);

        // 2) Lista todas as imagens de referência, excetuando a própria pasta tmp
        $allPaths = Storage::disk('s3')->allFiles('focinhos-smartdog');
        $paths = array_filter($allPaths, function($p) {
            return ! Str::startsWith($p, 'focinhos-smartdog/tmp/');
        });

        if (empty($paths)) {
            return response()->json([
                'recognized' => false,
                'message'    => 'Não há imagens de referência cadastradas.',
            ], 404);
        }

        // 3) Prepara chamadas assíncronas
        $client    = new Client(['timeout' => 15]);
        $threshold = 60;
        $promises  = [];

        foreach ($paths as $path) {
            try {
                $refContents = Storage::disk('s3')->get($path);

                $refTemp = tmpfile();
                $metaRef = stream_get_meta_data($refTemp);
                file_put_contents($metaRef['uri'], $refContents);

                $promises[$path] = $client->postAsync(
                    'https://api-cn.faceplusplus.com/imagepp/v2/dognosecompare',
                    [
                        'multipart' => [
                            ['name' => 'api_key',        'contents' => Config::get('services.megvii.key')],
                            ['name' => 'api_secret',     'contents' => Config::get('services.megvii.secret')],
                            ['name' => 'image_file',     'contents' => $uploadedContents,                   'filename' => 'user.' . $extension],
                            ['name' => 'image_ref_file', 'contents' => fopen($metaRef['uri'], 'r'),       'filename' => basename($path)],
                        ],
                    ]
                );
            } catch (\Exception $e) {
                Log::error("Erro comparando {$path}: {$e->getMessage()}");
            }
        }

        $results = Promise\Utils::settle($promises)->wait();

        // 4) Coleta todas as confidências
        $confidences = [];
        foreach ($results as $path => $result) {
            if ($result['state'] === 'fulfilled') {
                $data = json_decode($result['value']->getBody(), true);
                $conf  = $data['confidence'] ?? null;
                $confidences[$path] = $conf;
            } else {
                $confidences[$path] = 'error';
                Log::warning("Falha na resposta de {$path}: " . $result['reason']);
            }
        }

        // 5) Verifica correspondência acima do limiar
        foreach ($confidences as $path => $conf) {
            if (is_numeric($conf) && $conf >= $threshold) {
                return response()->json([
                    'recognized'     => true,
                    'reference_path' => $path,
                    'confidence'     => $conf,
                    'threshold'      => $threshold,
                    'uploaded_url'   => $uploadedUrl,
                    'confidences'    => $confidences,  // debug completo
                    'message'        => 'Focinho reconhecido com sucesso.',
                ]);
            }
        }

        // 6) Nenhuma passou
        return response()->json([
            'recognized'   => false,
            'message'      => 'Focinho não reconhecido em nenhuma imagem de referência.',
            'uploaded_url' => $uploadedUrl,
            'confidences'  => $confidences,  // debug completo
        ], 200);
    }
}
