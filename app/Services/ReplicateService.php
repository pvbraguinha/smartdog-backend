<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class ReplicateService
{
    protected string $apiUrl = 'https://api.replicate.com/v1/predictions';
    protected string $modelVersion = '3bb13fe1c33c35987b33792b01b71ed6529d03f165d1c2416375859f09ca9fef';

    public function generateImage(string $imageUrl, string $prompt): ?string
    {
        $seed = rand(1000, 999999); // Sempre gera um seed aleatório

        Log::info("Enviando imagem para Replicate com seed: {$seed}");

        $response = Http::withHeaders([
            'Authorization' => 'Token ' . env('REPLICATE_API_TOKEN'),
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
                'lora_weights' => 'https://pbxt.replicate.delivery/mwN3AFyYZyouOB03Uhw8ubKW9rpqMgdtL9zYV9GF2WGDiwbE/traine...', // 🔁 Insira o link completo aqui
                'refine_steps' => 20,
                'num_inference_steps' => 40
            ]
        ]);

        Log::info("Resposta da Replicate:", $response->json());

        if ($response->failed()) {
            Log::error("Falha na API Replicate: " . $response->body());
            return null;
        }

        $predictionId = $response->json('id');
        $statusUrl = $this->apiUrl . '/' . $predictionId;

        // Polling até a imagem estar pronta (timeout ~120s)
        for ($i = 0; $i < 60; $i++) {
            sleep(2);

            $poll = Http::withHeaders([
                'Authorization' => 'Token ' . env('REPLICATE_API_TOKEN'),
            ])->get($statusUrl);

            if ($poll->failed()) {
                Log::error("Erro ao consultar status da predição: " . $poll->body());
                return null;
            }

            $status = $poll->json('status');

            if ($status === 'succeeded') {
                return $poll->json('output')[0] ?? null;
            }

            if ($status === 'failed') {
                Log::error("Predição falhou: " . $poll->body());
                return null;
            }
        }

        Log::warning("Timeout: polling excedeu limite.");
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
                'prediction_id' => null, // Futuro uso
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
