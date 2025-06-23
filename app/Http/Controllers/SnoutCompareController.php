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
        // 1) Valida e prepara o focinho enviado pelo usuário
        $request->validate([
            'image' => 'required|image|max:5120',
        ]);

        // cria um temp file local para enviar ao Face++
        $uploadedFile = $request->file('image');
        $uploadedStream = fopen($uploadedFile->getRealPath(), 'r');
        $extension      = $uploadedFile->getClientOriginalExtension();

        // 2) Lista todas as imagens de referência (excluindo tmp)
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
                // pega o conteúdo da referência
                $refContents = Storage::disk('s3')->get($path);
                $refTemp      = tmpfile();
                $metaRef      = stream_get_meta_data($refTemp);
                file_put_contents($metaRef['uri'], $refContents);

                $promises[$path] = $client->postAsync(
                    'https://api-cn.faceplusplus.com/imagepp/v2/dognosecompare',
                    [
                        'multipart' => [
                            ['name' => 'api_key',        'contents' => Config::get('services.megvii.key')],
                            ['name' => 'api_secret',     'contents' => Config::get('services.megvii.secret')],
                            ['name' => 'image_file',     'contents' => $uploadedStream,       'filename' => 'user.'.$extension],
                            ['name' => 'image_ref_file', 'contents' => fopen($metaRef['uri'], 'r'), 'filename' => basename($path)],
                        ],
                    ]
                );
            } catch (\Exception $e) {
                // armazena a exception para debug
                $promises[$path] = Promise\rejection_for($e);
            }
        }

        // 4) Aguarda todas as respostas
        $results = Promise\Utils::settle($promises)->wait();

        // 5) Monta array de confidences e erros
        $confidences = [];
        foreach ($results as $path => $result) {
            if ($result['state'] === 'fulfilled') {
                $body = (string) $result['value']->getBody();
                $data = json_decode($body, true);
                $conf = $data['confidence'] ?? null;
                $confidences[$path] = is_null($conf) ? 'null' : $conf;
            } else {
                // pega a mensagem de erro da promise
                $reason = $result['reason'];
                $confidences[$path] = method_exists($reason, 'getMessage')
                    ? $reason->getMessage()
                    : (string) $reason;
                Log::warning("Erro comparando {$path}: " . $confidences[$path]);
            }
        }

        // 6) Verifica correspondência acima do threshold
        foreach ($confidences as $path => $conf) {
            if (is_numeric($conf) && $conf >= $threshold) {
                return response()->json([
                    'recognized'    => true,
                    'reference_path'=> $path,
                    'confidence'    => $conf,
                    'threshold'     => $threshold,
                    'confidences'   => $confidences,
                    'message'       => 'Focinho reconhecido com sucesso.',
                ]);
            }
        }

        // 7) Retorna debug completo se nada passar
        return response()->json([
            'recognized'   => false,
            'message'      => 'Focinho não reconhecido em nenhuma imagem de referência.',
            'confidences'  => $confidences,
        ], 200);
    }
}
