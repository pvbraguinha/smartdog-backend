<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use GuzzleHttp\Client;
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
        $file       = $request->file('image');
        $ext        = $file->getClientOriginalExtension();
        $tmpKey     = 'focinhos-smartdog/tmp/' . Str::random(20) . '.' . $ext;
        Storage::disk('s3')->put($tmpKey, file_get_contents($file->getRealPath()), 'public');
        $userUrl    = Storage::disk('s3')->url($tmpKey);

        // 2) Lista referências (excluindo tmp)
        $all = Storage::disk('s3')->allFiles('focinhos-smartdog');
        $paths = array_filter($all, fn($p) => ! Str::startsWith($p, 'focinhos-smartdog/tmp/'));
        if (empty($paths)) {
            return response()->json([
                'recognized' => false,
                'message'    => 'Não há imagens de referência.',
            ], 404);
        }

        // 3) Compara usando URLs no Face++
        $client    = new Client(['timeout' => 15]);
        $threshold = 60;
        $best = ['confidence' => 0, 'path' => null];

        foreach ($paths as $path) {
            $refUrl = Storage::disk('s3')->url($path);

            try {
                $res = $client->post('https://api-cn.faceplusplus.com/imagepp/v2/dognosecompare', [
                    'form_params' => [
                        'api_key'        => Config::get('services.megvii.key'),
                        'api_secret'     => Config::get('services.megvii.secret'),
                        'image_url'      => $userUrl,
                        'image_ref_url'  => $refUrl,
                    ],
                ]);

                $data = json_decode((string)$res->getBody(), true);
                $conf = $data['confidence'] ?? 0;
                Log::info("Comparando {$path} => {$conf}");

                if ($conf > $best['confidence']) {
                    $best = ['confidence' => $conf, 'path' => $path];
                }
            } catch (\Exception $e) {
                Log::warning("Erro comparando {$path}: " . $e->getMessage());
            }
        }

        // 4) Decide resultado
        if ($best['confidence'] >= $threshold) {
            return response()->json([
                'recognized'     => true,
                'reference_path' => $best['path'],
                'confidence'     => $best['confidence'],
                'threshold'      => $threshold,
                'user_url'       => $userUrl,
                'message'        => 'Focinho reconhecido com sucesso.',
            ]);
        }

        return response()->json([
            'recognized'     => false,
            'confidence_max' => $best['confidence'],
            'threshold'      => $threshold,
            'user_url'       => $userUrl,
            'message'        => 'Nenhuma referência passou do threshold.',
        ], 200);
    }
}
