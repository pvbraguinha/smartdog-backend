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
        // 1) Valida e faz upload temporário do focinho do usuário
        $request->validate([
            'image' => 'required|image|max:5120',
        ]);

        $file   = $request->file('image');
        $ext    = $file->getClientOriginalExtension();
        $tmpKey = 'focinhos-smartdog/tmp/' . Str::random(20) . '.' . $ext;

        Storage::disk('s3')->put(
            $tmpKey,
            file_get_contents($file->getRealPath()),
            'public'
        );

        $userUrl = Storage::disk('s3')->url($tmpKey);

        // 2) Lista referências (excluindo tmp)
        $allPaths = Storage::disk('s3')->allFiles('focinhos-smartdog');
        $paths = array_filter($allPaths, fn($p) => !Str::startsWith($p, 'focinhos-smartdog/tmp/'));

        if (empty($paths)) {
            return response()->json([
                'recognized' => false,
                'message'    => 'Não há imagens de referência cadastradas.',
            ], 404);
        }

        // 3) Prepara requisições assíncronas
        $client    = new Client(['timeout' => 10]);
        $threshold = 60;
        $promises  = [];
        $debug     = [];

        foreach ($paths as $path) {
            $refUrl = Storage::disk('s3')->url($path);
            $promises[$path] = $client->postAsync(
                'https://api-cn.faceplusplus.com/imagepp/v2/dognosecompare',
                [
                    'form_params' => [
                        'api_key'       => Config::get('services.megvii.key'),
                        'api_secret'    => Config::get('services.megvii.secret'),
                        'image_url'     => $userUrl,
                        'image_ref_url' => $refUrl,
                    ],
                ]
            )->then(
                function ($response) use (&$debug, $path) {
                    $data = json_decode((string)$response->getBody(), true);
                    $debug[$path] = $data;
                },
                function ($reason) use (&$debug, $path) {
                    $error = $reason instanceof \Exception ? $reason->getMessage() : (string)$reason;
                    $debug[$path] = ['error' => $error];
                    Log::warning("Erro comparando {$path}: {$error}");
                }
            );
        }

        // 4) Executa todas em paralelo e espera
        Promise\Utils::settle($promises)->wait();

        // 5) Avalia o melhor resultado
        $best = ['confidence' => 0, 'path' => null];
        foreach ($debug as $path => $data) {
            $conf = $data['confidence'] ?? 0;
            if (is_numeric($conf) && $conf > $best['confidence']) {
                $best = ['confidence' => $conf, 'path' => $path];
            }
        }

        // 6) Retorna resposta
        return response()->json([
            'recognized'     => $best['confidence'] >= $threshold,
            'reference_path' => $best['path'],
            'confidence_max' => $best['confidence'],
            'threshold'      => $threshold,
            'user_url'       => $userUrl,
            'debug'          => $debug,
            'message'        => $best['confidence'] >= $threshold
                ? 'Focinho reconhecido com sucesso.'
                : 'Nenhuma referência passou do threshold.',
        ], 200);
    }
}
