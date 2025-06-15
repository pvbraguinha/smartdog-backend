<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;

class DalleService
{
    public function gerarImagem(string $prompt): string
    {
        $apiKey = env("OPENAI_API_KEY");

        $response = Http::withToken($apiKey)
            ->post("https://api.openai.com/v1/images/generations", [
                "model" => "dall-e-3",
                "prompt" => $prompt,
                "n" => 1,
                "size" => "1024x1024",
                "response_format" => "url",
            ]);

        if ($response->successful()) {
            $dalleImageUrl = $response->json("data.0.url");

            if (!$dalleImageUrl) {
                throw new \Exception("DALL-E não retornou uma URL de imagem.");
            }

            // Baixar a imagem do DALL-E
            $imageContents = file_get_contents($dalleImageUrl);
            if ($imageContents === false) {
                throw new \Exception("Não foi possível baixar a imagem do DALL-E.");
            }

            // Salvar a imagem no S3
            $filename = "uploads/meupethumano/dalle_results/" . uniqid() . ".jpg";
            Storage::disk("s3")->put($filename, $imageContents, "public");

            Log::info("Imagem DALL-E salva no S3: " . Storage::disk("s3")->url($filename));

            return Storage::disk("s3")->url($filename);
        }

        Log::error("Erro ao gerar imagem com DALL-E: " . $response->body());
        throw new \Exception("Erro ao gerar imagem: " . $response->body());
    }
}
