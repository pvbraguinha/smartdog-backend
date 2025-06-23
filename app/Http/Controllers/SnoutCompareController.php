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

        $uploadFile = $request->file('image');
        $ext        = $uploadFile->getClientOriginalExtension();
        $tmpKey     = 'focinhos-smartdog/tmp/' . Str::random(20) . '.' . $ext;

        // Armazena temporariamente no S3
        Storage::disk('s3')->put(
            $tmpKey,
            file_get_contents($uploadFile->getRealPath()),
            'public'
        );

        // Prepara stream para multipart
        $userStream = fopen($uploadFile->getRealPath(), 'r');

        // 2) Lista referências (excluindo tmp)
        $allPaths = Storage::disk('s3')->allFiles('focinhos-smartdog');
        $refs     = array_filter($allPaths, fn($p) => !Str::startsWith($p, 'focinhos-smartdog/tmp/'));

        if (empty($refs)) {
            return response()->json([
                'recognized' => false,
                'message'    => 'Não há imagens de referência cadastradas.',
            ], 404);
        }

        // 3) Compara cada referência usando multipart/form-data
        $client    = new Client(['timeout' => 20]);
        $threshold = 60;
        $best      = ['confidence' => 0, 'path' => null];
        $promises  = [];

        foreach ($refs as $path) {
            // Baixa o binário da referência
            $content = Storage::disk('s3')->get($path);
            $refTemp = tmpfile();
            $mdata   = stream_get_meta_data($refTemp);
            file_put_contents($mdata['uri'], $content);

            $promises[$path] = $client->postAsync(
                'https://api-cn.faceplusplus.com/imagepp/v2/dognosecompare',
                [
                    'multipart' => [
                        ['name'=>'api_key',        'contents'=>Config::get('services.megvii.key')],
                        ['name'=>'api_secret',     'contents'=>Config::get('services.megvii.secret')],
                        ['name'=>'image_file',     'contents'=>fopen($uploadFile->getRealPath(),'r'), 'filename'=>'user.'.$ext],
                        ['name'=>'image_ref_file', 'contents'=>fopen($mdata['uri'],'r'),         'filename'=>basename($path)],
                    ],
                ]
            );
        }

        // Aguarda todas as comparações
        $results = Promise\Utils::settle($promises)->wait();

        // 4) Avalia melhores resultados
        foreach ($results as $path => $res) {
            if ($res['state'] === 'fulfilled') {
                $data = json_decode((string)$res['value']->getBody(), true);
                $conf = $data['confidence'] ?? 0;
                Log::info("Comparando {$path} => {$conf}");

                if (is_numeric($conf) && $conf > $best['confidence']) {
                    $best = ['confidence' => $conf, 'path' => $path];
                }
            } else {
                Log::warning("Erro comparando {$path}: " . $res['reason']);
            }
        }

        // 5) Retorna resultado
        return response()->json([
            'recognized'     => $best['confidence'] >= $threshold,
            'reference_path' => $best['path'],
            'confidence_max' => $best['confidence'],
            'threshold'      => $threshold,
            'message'        => $best['confidence'] >= $threshold
                                 ? 'Focinho reconhecido com sucesso.'
                                 : 'Nenhuma referência passou do threshold.',
        ], 200);
    }
}
