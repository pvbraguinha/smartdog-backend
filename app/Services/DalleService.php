<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Log;

class DalleService
{
    public function gerarImagemComPrompt(string $prompt): string
    {
        try {
            $response = Http::withToken(env('OPENAI_API_KEY'))
                ->timeout(40)
                ->post('https://api.openai.com/v1/images/generations', [
                    'prompt' => $prompt,
                    'n' => 1,
                    'size' => '512x512',
                    'response_format' => 'b64_json'
                ]);

            if ($response->failed()) {
                throw new \Exception('Erro ao chamar API do DALL·E: ' . $response->body());
            }

            $imageBase64 = $response->json('data.0.b64_json');

            // Salvar imagem no S3
            $filename = 'dalle_outputs/' . now()->format('Ymd_His') . '_' . Str::random(10) . '.png';
            Storage::disk('s3')->put($filename, base64_decode($imageBase64), 'public');

            return Storage::disk('s3')->url($filename);

        } catch (\Exception $e) {
            Log::error("Erro no DalleService: " . $e->getMessage());
            throw $e;
        }
    }
}
