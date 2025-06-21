<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Dog;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use GuzzleHttp\Client;
use GuzzleHttp\Promise;
use Illuminate\Support\Facades\Log;

class SnoutCompareController extends Controller
{
    public function compare(Request $request)
    {
        $validated = $request->validate([
            'image' => 'required|image'
        ]);

        $dateFolder = now()->format('Y-m-d');
        $tempFilename = "focinhos-smartdog/tmp/" . Str::random(20) . '.' . $request->file('image')->getClientOriginalExtension();
        Storage::disk('s3')->put($tempFilename, file_get_contents($request->file('image')), 'public');
        $uploadedUrl = Storage::disk('s3')->url($tempFilename);

        // Faz download da imagem enviada para memória
        $tempImage = tmpfile();
        $meta = stream_get_meta_data($tempImage);
        file_put_contents($meta['uri'], file_get_contents($uploadedUrl));

        $client = new Client(['timeout' => 15]);
        $threshold = 72;
        $dogs = Dog::whereNotNull('photo_url')->get();

        $promises = [];

        foreach ($dogs as $dog) {
            // Verifica se a URL está acessível antes de continuar
            $imageContent = @file_get_contents($dog->photo_url);
            if (!$dog->photo_url || !$imageContent) {
                Log::warning("Imagem inválida ou inacessível para o dog ID {$dog->id}: {$dog->photo_url}");
                continue;
            }

            $refTemp = tmpfile();
            $metaRef = stream_get_meta_data($refTemp);
            file_put_contents($metaRef['uri'], $imageContent);

            try {
                $promises[$dog->id] = $client->postAsync('https://api-cn.faceplusplus.com/imagepp/v2/dognosecompare', [
                    'multipart' => [
                        ['name' => 'api_key', 'contents' => env('MEGVI_API_KEY')],
                        ['name' => 'api_secret', 'contents' => env('MEGVI_API_SECRET')],
                        ['name' => 'image_file', 'contents' => fopen($meta['uri'], 'r')],
                        ['name' => 'image_ref_file', 'contents' => fopen($metaRef['uri'], 'r')],
                    ]
                ]);
            } catch (\Exception $e) {
                Log::error("Erro ao criar comparação async para o dog ID {$dog->id}: {$e->getMessage()}");
                continue;
            }
        }

        $results = Promise\Utils::settle($promises)->wait();

        foreach ($results as $dogId => $result) {
            if ($result['state'] === 'fulfilled') {
                $data = json_decode($result['value']->getBody(), true);
                if (isset($data['confidence']) && $data['confidence'] >= $threshold) {
                    $dog = Dog::find($dogId);
                    return response()->json([
                        'recognized' => true,
                        'dog_id' => $dog->id,
                        'name' => $dog->name,
                        'status' => $dog->status,
                        'phone' => $dog->status === 'perdido' ? $dog->phone : null,
                        'confidence' => $data['confidence'],
                        'threshold' => $threshold,
                        'photo_url' => $dog->photo_url,
                        'message' => 'Cão reconhecido com sucesso.'
                    ]);
                }
            }
        }

        return response()->json([
            'recognized' => false,
            'message' => 'Focinho não reconhecido em nenhum dos cães registrados.',
            'photo_uploaded' => $uploadedUrl
        ]);
    }
}
