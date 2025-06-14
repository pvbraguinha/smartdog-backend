<?php

namespace App\Services;

use App\Models\TransformationHistory;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;

class PetTransformationService
{
    private $promptGenerator;
    private $replicate;
    private $imageCompositionService;

    public function __construct(
        PromptGeneratorService $promptGenerator,
        ReplicateService $replicate,
        ImageCompositionService $imageCompositionService
    ) {
        $this->promptGenerator = $promptGenerator;
        $this->replicate = $replicate;
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

            Log::info("Prompt gerado", ["prompt" => $prompt]);

            $controlImageUrl = $this->getControlImageUrl($petImages);

            Log::info('🐾 Imagem de controle enviada para Replicate:', ['url' => $controlImageUrl]);

            if (empty($controlImageUrl)) {
                throw new \Exception("Nenhuma imagem frontal foi enviada para a transformação.");
            }

            $replicateResult = $this->replicate->transformPetToHuman($controlImageUrl, $prompt, $negativePrompt);

            if (!$replicateResult["success"]) {
                throw new \Exception("Falha na transformação: " . $replicateResult["error"]);
            }

            $this->updateTransformationHistory($userSession, $manualBreed, $replicateResult, $sex, $age);

            $compositeImageUrl = $this->createSideBySideComposition(
                $controlImageUrl,
                $replicateResult["output_url"],
                $userSession
            );

            return [
                "success" => true,
                "especie" => $especie,
                "breed_detected" => $manualBreed,
                "original_image" => $controlImageUrl,
                "transformed_image" => $replicateResult["output_url"],
                "composite_image" => $compositeImageUrl,
                "prompt_used" => $prompt,
                "processing_time" => $replicateResult["processing_time"],
                "breed" => $manualBreed,
                "sex" => $sex,
                "age" => $age,
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

    private function updateTransformationHistory($userSession, $breed, $replicateResult, $sex = null, $age = null)
    {
        $history = TransformationHistory::where("user_session", $userSession)
            ->where("breed_detected", $breed)
            ->latest()
            ->first();

        $data = [
            "replicate_prediction_id" => $replicateResult["prediction_id"],
            "result_image_url" => $replicateResult["output_url"],
            "breed" => $breed,
            "sex" => $sex,
            "age" => $age,
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
