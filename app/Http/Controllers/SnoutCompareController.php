<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Intervention\Image\Facades\Image;
use GuzzleHttp\Client;
use GuzzleHttp\Promise;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Config;

class SnoutCompareController extends Controller
{
    public function compare(Request $request)
    {
        // 1) Valida e limita upload (até 10MB)
        $request->validate([
            'image' => 'required|image|max:10240',
        ]);
        $uploadFile = $request->file('image');
        $ext        = $uploadFile->getClientOriginalExtension();

        // 2) Redimensiona/comprime upload para ~800x800, qualidade 75%
        $imgUser = Image::make($uploadFile->getRealPath())
                        ->resize(800, 800, fn($c) => $c->aspectRatio()->upsize())
                        ->encode('jpg', 75);
        $tmpUser = tmpfile();
        $metaU   = stream_get_meta_data($tmpUser);
        fwrite($tmpUser, (string)$imgUser);

        // 3) Lista referências (excluindo tmp)
        $allPaths = Storage::disk('s3')->allFiles('focinhos-smartdog');
        $refs     = array_filter($allPaths, fn($p) => !Str::startsWith($p, 'focinhos-smartdog/tmp/'));
        if (empty($refs)) {
            return response()->json([
                'recognized' => false,
                'message'    => 'Não há imagens de referência cadastradas.',
            ], 404);
        }

        // 4) Monta chamadas assíncronas multipart
        $client    = new Client(['timeout' => 30]);
        $threshold = 60;
        $best      = ['confidence' => 0, 'path' => null];
        $promises  = [];

        foreach ($refs as $path) {
            // baixa e redimensiona referência
            $content = Storage::disk('s3')->get($path);
            $imgRef  = Image::make($content)
                            ->resize(800, 800, fn($c) => $c->aspectRatio()->upsize())
                            ->encode('jpg', 75);
            $tmpRef  = tmpfile();
            $metaR   = stream_get_meta_data($tmpRef);
            fwrite($tmpRef, (string)$imgRef);

            $promises[$path] = $client->postAsync(
                'https://api-cn.faceplusplus.com/imagepp/v2/dognosecompare',
                [
                    'multipart' => [
                        ['name' => 'api_key',        'contents' => Config::get('services.megvii.key')],
                        ['name' => 'api_secret',     'contents' => Config::get('services.megvii.secret')],
                        ['name' => 'image_file',     'contents' => fopen($metaU['uri'], 'r'), 'filename' => 'user.'.$ext],
                        ['name' => 'image_ref_file', 'contents' => fopen($metaR['uri'], 'r'), 'filename' => basename($path)],
                    ],
                ]
            );
        }

        // 5) Aguarda todas
        $results = Promise\Utils::settle($promises)->wait();

        // 6) Avalia confidences
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
