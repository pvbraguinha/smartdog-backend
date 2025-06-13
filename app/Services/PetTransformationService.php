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

    public function transformPet($petImages, $userSession, $manualBreed)
    {
        try {
            $detectedBreed = $manualBreed;

            if (empty($detectedBreed)) {
                throw new \Exception("A raça do pet deve ser fornecida pelo usuário.");
            }

            Log::info("Raça fornecida pelo usuário: {$detectedBreed}");

            $prompt = $this->promptGenerator->generate($detectedBreed);

            Log::info("Prompt gerado", ["prompt" => $prompt]);

            $controlImageUrl = $this->getControlImageUrl($petImages);

            $replicateResult = $this->replicate->transformPetToHuman($controlImageUrl, $prompt);

            if (!$replicateResult["success"]) {
                throw new \Exception("Falha na transformação: " . $replicateResult["error"]);
            }

            $this->updateTransformationHistory($userSession, $detectedBreed, $replicateResult);

            // Cria a imagem composta (usando sempre a assinatura correta)
            $compositeImageUrl = $this->createSideBySideComposition(
                $controlImageUrl,
                $replicateResult["output_url"],
                $userSession
            );

            return [
                "success" => true,
                "breed_detected" => $detectedBreed,
                "original_image" => $controlImageUrl,
                "transformed_image" => $replicateResult["output_url"],
                "composite_image" => $compositeImageUrl,
                "prompt_used" => $prompt,
                "processing_time" => $replicateResult["processing_time"]
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
        return $petImages["frontal"] ?? $petImages["focinho"] ?? $petImages["angulo"];
    }

    private function updateTransformationHistory($userSession, $breed, $replicateResult)
    {
        $history = TransformationHistory::where("user_session", $userSession)
            ->where("breed_detected", $breed)
            ->latest()
            ->first();

        if ($history) {
            $history->update([
                "replicate_prediction_id" => $replicateResult["prediction_id"],
                "result_image_url" => $replicateResult["output_url"]
            ]);
        } else {
            TransformationHistory::create([
                "user_session" => $userSession,
                "breed_detected" => $breed,
                "replicate_prediction_id" => $replicateResult["prediction_id"],
                "result_image_url" => $replicateResult["output_url"]
            ]);
        }
    }

    private function createSideBySideComposition($originalUrl, $transformedUrl, $userSession)
    {
        // Só usa uma assinatura, sempre
        $result = $this->imageCompositionService->createProfessionalComposition(
            $originalUrl,
            $transformedUrl,
            $userSession
        );

        // Se falhar, retorna a transformada pura (fallback)
        return $result["success"] ? $result["composition_url"] : $transformedUrl;
    }
}
