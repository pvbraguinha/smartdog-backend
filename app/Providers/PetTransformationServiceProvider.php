<?php

namespace App\Services;

use App\Models\TransformationHistory;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;

class PetTransformationService
{
    private $promptGenerator;
    private $dalle;
    private $imageCompositionService;

    public function __construct(
        PromptGeneratorService $promptGenerator,
        DalleService $dalle,
        ImageCompositionService $imageCompositionService
    ) {
        $this->promptGenerator = $promptGenerator;
        $this->dalle = $dalle;
        $this->imageCompositionService = $imageCompositionService;
    }

    public function transformPet($petImages, $userSession, $especie, $manualBreed, $sex = null, $age = null)
    {
        try {
            if (empty($especie) || empty($manualBreed)) {
                throw new \Exception("Espécie e raça do pet devem ser fornecidas pelo usuário.");
            }
            if (empty($sex) || empty($age)) {
                throw new \Exception("Sexo e idade do pet são obrigatórios.");
            }

            Log::info("Espécie fornecida pelo usuário: {$especie}, Raça: {$manualBreed}");

            $prompt = $this->promptGenerator->generate($especie, $manualBreed, $sex, $age);
            $negativePrompt = $this->promptGenerator->generateNegativePrompt();
            $idadeHumana = $this->promptGenerator->calcularIdadeHumana($especie, $age);

            Log::info("Prompt gerado", ["prompt" => $prompt]);

            // DALL-E não usa imagem de controle, apenas o prompt
            $dalleResultUrl = $this->dalle->gerarImagem($prompt);

            if (empty($dalleResultUrl)) {
                throw new \Exception("Falha na transformação: DALL-E não retornou uma imagem.");
            }

            // A imagem original para a composição ainda é necessária, mesmo que DALL-E não a use para geração
            $originalImageUrl = $this->getControlImageUrl($petImages);
            if (empty($originalImageUrl)) {
                throw new \Exception("Nenhuma imagem frontal foi enviada para a composição.");
            }

            $this->updateTransformationHistory($userSession, $manualBreed, $dalleResultUrl, $sex, $age);

            $compositeImageUrl = $this->createSideBySideComposition(
                $originalImageUrl,
                $dalleResultUrl,
                $userSession
            );

            return [
                "success" => true,
                "especie" => $especie,
                "breed_detected" => $manualBreed,
                "original_image" => $originalImageUrl,
                "transformed_image" => $dalleResultUrl,
                "composite_image" => $compositeImageUrl,
                "prompt_used" => $prompt,
                "processing_time" => 0, // DALL-E não retorna tempo de processamento diretamente aqui
                "breed" => $manualBreed,
                "sex" => $sex,
                "age" => $age,
                "idade_humana" => $idadeHumana,
            ];

        } catch (\Exception $e) {
            Log::error("Erro na transformação: " . $e->getMessage());
            return [
                "success" => false,
                "error" => $e->getMessage()
            ];
        }
    }

    private function getControlImageUrl($petImages)
    {
        return $petImages["frontal"] ?? null;
    }

    private function updateTransformationHistory($userSession, $breed, $dalleResultUrl, $sex = null, $age = null)
    {
        $history = TransformationHistory::where("user_session", $userSession)
            ->where("breed_detected", $breed)
            ->latest()
            ->first();

        $data = [
            "result_image_url" => $dalleResultUrl,
            "breed" => $breed,
            "sex" => $sex,
            "age" => $age,
            "replicate_prediction_id" => null, // Replicate não é mais usado
        ];

        if ($history) {
            $history->update($data);
        } else {
            TransformationHistory::create(array_merge([
                "user_session" => $userSession,
                "breed_detected" => $breed,
            ], $data));
        }
    }

    private function createSideBySideComposition($originalUrl, $transformedUrl, $userSession)
    {
        $result = $this->imageCompositionService->createProfessionalComposition(
            $originalUrl,
            $transformedUrl,
            $userSession
        );

        return $result["success"] ? $result["composition_url"] : $transformedUrl;
    }
}