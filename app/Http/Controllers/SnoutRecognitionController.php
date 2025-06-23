<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Dog;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use GuzzleHttp\Client;
use GuzzleHttp\Promise;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Config;

class SnoutRecognitionController extends Controller
{
    public function recognize(Request $request)
    {
        $validated = $request->validate([
            'image' => 'required|image'
        ]);

        // Salvar imagem temporária no S3
        $dateFolder = now()->format('Y-m-d');
        $tempFilename = "focinhos-smartdog/tmp/" . Str::random(20) . '.' . $request->file('image')->getClientOriginalExtension();
        Storage::disk('s3')->put($tempFilename, file_get_contents($request->file('image')), 'public');
        $uploadedUrl = Storage::disk('s3')->url($tempFilename);

        // Baixar para memória
        $tempImage = tmpfile();
        $meta = stream_get_meta_data($tempImage);
        file_put_contents($meta['uri'], file_get_contents($uploadedUrl));

        $client = new Client(['timeout' => 20]);
        $threshold = 60;
        $dogs = Dog::whereNotNull('photo_url')->get();
        $promises = [];

        foreach ($dogs as $dog) {
            $imageContent = @file_get_contents($dog->photo_url);
            if (!$imageContent) {
                Log::warning("⚠️ Imagem inacessível para o dog ID {$dog->id}: {$dog->photo_url}");
                continue;
            }

            $refTemp = tmpfile();
            $metaRef = stream_get_meta_data($refTemp);
            file_put_contents($metaRef['uri'], $imageContent);

            try {
                $promises[$dog->id] = $client->postAsync('https://api-cn.faceplusplus.com/imagepp/v2/dognosecompare', [
                    'multipart' => [
                        ['name' => 'api_key', 'contents' => Config::get('services.megvi.key')],
                        ['name' => 'api_secret', 'contents' => Config::get('services.megvi.secret')],
                        ['name' => 'image_file', 'contents' => fopen($meta['uri'], 'r')],
                        ['name' => 'image_ref_file', 'contents' => fopen($metaRef['uri'], 'r')],
                    ]
                ]);
            } catch (\Exception $e) {
                Log::error("❌ Erro ao criar requisição async: {$e->getMessage()}");
                continue;
            }
        }

        $results = Promise\Utils::settle($promises)->wait();

        foreach ($results as $dogId => $result) {
            if ($result['state'] === 'fulfilled') {
                $data = json_decode($result['value']->getBody(), true);
                $confidence = $data['confidence'] ?? 0;

                Log::info("🐾 Dog ID {$dogId} - Confiança: {$confidence}");

                if ($confidence >= $threshold) {
                    $dog = Dog::find($dogId);
                    Log::info("✅ Cão reconhecido: {$dog->name} (ID {$dog->id})");

                    return response()->json([
                        'recognized' => true,
                        'dog_id' => $dog->id,
                        'name' => $dog->name,
                        'status' => $dog->status,
                        'phone' => $dog->status === 'perdido' ? $dog->phone : null,
                        'confidence' => $confidence,
                        'photo_url' => $dog->photo_url,
                        'message' => 'Cão reconhecido com sucesso.'
                    ]);
                }
            }
        }

        return response()->json([
            'recognized' => false,
            'message' => 'Nenhum focinho correspondente encontrado.',
            'photo_uploaded' => $uploadedUrl
        ]);
    }
}
