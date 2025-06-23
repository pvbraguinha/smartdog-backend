<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Intervention\Image\Facades\Image;
use GuzzleHttp\Client;
use GuzzleHttp\Promise;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Log;

class SnoutCompareController extends Controller
{
    public function compare(Request $request)
    {
        // 1) Valida upload (até 10MB)
        $request->validate([
            'image' => 'required|image|max:10240',
        ]);

        $uploadFile = $request->file('image');
        $ext        = $uploadFile->getClientOriginalExtension();

        // 2) Tenta redimensionar/comprimir o upload
        try {
            $imgUser = Image::make($uploadFile->getRealPath())
                            ->resize(800, 800, function($c) {
                                $c->aspectRatio();
                                $c->upsize();
                            })
                            ->encode('jpg', 75);

            $tmpUser = tmpfile();
            $metaU   = stream_get_meta_data($tmpUser);
            fwrite($tmpUser, (string)$imgUser);
            $userUri = $metaU['uri'];
        } catch (\Throwable $e) {
            Log::warning("Redimensionamento falhou ({$e->getMessage()}), usando original.");
            $userUri = $uploadFile->getRealPath();
        }

        // 3) Lista referências (excluindo tmp)
        $allPaths = Storage::disk('s3')->allFiles('focinhos-smartdog');
        $refs     = array_filter($allPaths, fn($p) => !Str::startsWith($p, 'focinhos-smartdog/tmp/'));

        if (empty($refs)) {
            return response()->json([
                'recognized' => false,
                'message'    => 'Não há imagens de referência cadastradas.',
            ], 404);
        }

        // 4) Prepara chamadas assíncronas multipart
        $client    = new Client(['timeout' => 30]);
        $threshold = 50;
        $best      = ['confidence' => 0, 'path' => null];
        $promises  = [];

        foreach ($refs as $path) {
            // baixa referência e tenta redimensionar/comprimir
            $content = Storage::disk('s3')->get($path);

            try {
                $imgRef = Image::make($content)
                               ->resize(800, 800, function($c) {
                                   $c->aspectRatio();
                                   $c->upsize();
                               })
                               ->encode('jpg', 75);

                $tmpRef = tmpfile();
                $metaR  = stream_get_meta_data($tmpRef);
                fwrite($tmpRef, (string)$imgRef);
                $refUri = $metaR['uri'];
            } catch (\Throwable $e) {
                Log::warning("Redimensionamento falhou em {$path}, usando original.");
                $tmpRef = tmpfile();
                $metaR  = stream_get_meta_data($tmpRef);
                file_put_contents($metaR['uri'], $content);
                $refUri = $metaR['uri'];
            }

            $promises[$path] = $client->postAsync(
                'https://api-cn.faceplusplus.com/imagepp/v2/dognosecompare',
                [
                    'multipart' => [
                        ['name'=>'api_key',        'contents'=>Config::get('services.megvii.key')],
                        ['name'=>'api_secret',     'contents'=>Config::get('services.megvii.secret')],
                        ['name'=>'image_file',     'contents'=>fopen($userUri, 'r'),  'filename'=>'user.'.$ext],
                        ['name'=>'image_ref_file', 'contents'=>fopen($refUri,  'r'),  'filename'=>basename($path)],
                    ],
                ]
            );
        }

        // 5) Aguarda todas as comparações
        $results = Promise\Utils::settle($promises)->wait();

        // 6) Avalia confidences e encontra melhor
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

        // 7) Retorna resultado
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
