<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class ReplicateService
{
    protected string $apiUrl = 'https://api.replicate.com/v1/predictions';
    protected string $modelVersion;

    public function __construct()
    {
        $this->modelVersion = config('services.replicate.version');
    }

    public function generateImage(string $imageUrl, string $prompt): ?string
    {
        $seed = rand(1000, 999999);

        Log::info("Enviando imagem para Replicate com seed: {$seed}");

        $response = Http::withHeaders([
            'Authorization' => 'Token ' . config('services.replicate.token'),
            'Content-Type' => 'application/json'
        ])->post($this->apiUrl, [
            'version' => $this->modelVersion,
            'input' => [
                'image' => $imageUrl,
                'prompt' => $prompt,
                'img2img' => true,
                'condition_scale' => 0.5,
                'strength' => 0.8,
                'seed' => $seed,
                'refine_steps' => 20,
                'num_inference_steps' => 40,
                'lora_weights' => 'https://pbxt.replicate.delivery/mwN3AFyYZyouOB03Uhw8ubKW9rpqMgdtL9zYV9GF2WGDiwbE/trained_model.tar'
            ]
        ]);

        Log::info("Resposta da Replicate:", $response->json());

        if ($response->failed()) {
            Log::error("Falha na API Replicate: " . $response->body());
            return null;
        }

        $predictionId = $response->json('id');
        $statusUrl = $this->apiUrl . '/' . $predictionId;

        for ($i = 0; $i < 60; $i++) {
            sleep(2);

            $poll = Http::withHeaders([
                'Authorization' => 'Token ' . config('services.replicate.token'),
            ])->get($statusUrl);

            if ($poll->failed()) {
                Log::error("Erro ao consultar status da predição: " . $poll->body());
                return null;
            }

            if ($poll->json('status') === 'succeeded') {
                $outputUrl = $poll->json('output')[0] ?? null;

                if (!$outputUrl) {
                    Log::error("Nenhuma URL de saída retornada.");
                    return null;
                }

                $contents = file_get_contents($outputUrl);
                $filename = "uploads/meupethumano/resultados/" . uniqid() . ".jpg";
                Storage::disk('s3')->put($filename, $contents, 'public');

                return Storage::disk('s3')->url($filename);
            }

            if ($poll->json('status') === 'failed') {
                Log::error("Predição falhou: " . $poll->body());
                return null;
            }
        }

        Log::warning("Timeout: polling excedeu o limite.");
        return null;
    }

    public function transformPetToHuman(string $imageUrl, string $prompt): array
    {
        try {
            $start = microtime(true);

            $resultUrl = $this->generateImage($imageUrl, $prompt);

            if (!$resultUrl) {
                return [
                    'success' => false,
                    'error' => 'A API do Replicate não retornou uma imagem.',
                ];
            }

            $processingTime = round(microtime(true) - $start, 2);

            return [
                'success' => true,
                'output_url' => $resultUrl,
                'prediction_id' => null,
                'processing_time' => $processingTime,
            ];
        } catch (\Exception $e) {
            Log::error("Erro na transformação com Replicate: " . $e->getMessage());

            return [
                'success' => false,
                'error' => 'Erro ao chamar o Replicate: ' . $e->getMessage(),
            ];
        }
    }
}

