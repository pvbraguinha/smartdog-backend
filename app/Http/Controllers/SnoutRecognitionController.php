<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Dog;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use GuzzleHttp\Client;

class SnoutRecognitionController extends Controller
{
    public function detect(Request $request)
    {
        $validated = $request->validate([
            'image' => 'required|image'
        ]);

        // Salva imagem no S3 em pasta focinhos-smartdog/yyyy-mm-dd
        $dateFolder = now()->format('Y-m-d');
        $filename = "focinhos-smartdog/{$dateFolder}/" . Str::random(15) . '.' . $request->file('image')->getClientOriginalExtension();
        Storage::disk('s3')->put($filename, file_get_contents($request->file('image')), 'public');
        $imageUrl = Storage::disk('s3')->url($filename);

        // Converte imagem para base64
        $imageBase64 = base64_encode(file_get_contents($request->file('image')->getRealPath()));

        try {
            $client = new Client();
            $res = $client->post('https://api-cn.faceplusplus.com/imagepp/v2/dognosedetect', [
                'form_params' => [
                    'api_key' => env('MEGVI_API_KEY'),
                    'api_secret' => env('MEGVI_API_SECRET'),
                    'image_base64' => $imageBase64,
                ],
                'timeout' => 20,
            ]);

            $response = json_decode($res->getBody(), true);

            if (!isset($response['confidence']) || $response['confidence'] < 0.85) {
                return response()->json([
                    'success' => false,
                    'message' => 'Focinho não reconhecido com confiança suficiente.',
                    'confidence' => $response['confidence'] ?? null,
                    'image_url' => $imageUrl
                ], 404);
            }

            // Simula retorno com o Dog ID 1
            $dog = Dog::find(1);
            if (!$dog) {
                return response()->json([
                    'success' => false,
                    'message' => 'Cão simulado (ID 1) não encontrado no banco.'
                ], 404);
            }

            return response()->json([
                'success' => true,
                'dog_id' => $dog->id,
                'name' => $dog->name,
                'status' => $dog->status,
                'phone' => $dog->status === 'perdido' ? $dog->phone : null,
                'confidence' => $response['confidence'],
                'image_url' => $imageUrl,
                'message' => 'Focinho reconhecido com sucesso.'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erro ao processar a imagem com a Megvi.',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
