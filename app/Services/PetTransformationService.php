<?php
// app/Services/PetTransformationService.php

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

    /**
     * Processo completo de transformação
     */
    public function transformPet($petImages, $userSession, $manualBreed)
    {
        try {
            $detectedBreed = $manualBreed;

            if (empty($detectedBreed)) {
                throw new \Exception("A raça do pet deve ser fornecida pelo usuário.");
            }

            Log::info("Raça fornecida pelo usuário: {$detectedBreed}");

            // Corrigido aqui: método correto é "generate"
            $prompt = $this->promptGenerator->generate($detectedBreed);

            Log::info("Prompt gerado", ["prompt" => $prompt]);

            // 3. Usar imagem frontal como controle
            $controlImageUrl = $this->getControlImageUrl($petImages);

            // 4. Chamar Replicate
            $replicateResult = $this->replicate->transformPetToHuman($controlImageUrl, $prompt);

            if (!$replicateResult["success"]) {
                throw new \Exception("Falha na transformação: " . $replicateResult["error"]);
            }

            // 5. Salvar resultado no histórico
            $this->updateTransformationHistory($userSession, $detectedBreed, $replicateResult);

            // 6. Criar composição lado a lado
            $compositeImageUrl = $this->createSideBySideComposition(
                $controlImageUrl,
                $replicateResult["output_url"],
                $userSession,
                $detectedBreed
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
        TransformationHistory::where("user_session", $userSession)
            ->where("breed_detected", $breed)
            ->latest()
            ->first()
            ->update([
                "replicate_prediction_id" => $replicateResult["prediction_id"],
                "result_image_url" => $replicateResult["output_url"]
            ]);
    }

    private function createSideBySideComposition($originalUrl, $transformedUrl, $userSession, $breedName)
    {
        $result = $this->imageCompositionService->createProfessionalComposition(
            $originalUrl,
            $transformedUrl,
            $breedName,
            $userSession
        );

        if ($result["success"]) {
            return $result["composition_url"];
        }

        $simpleResult = $this->imageCompositionService->createSideBySideComposition(
            $originalUrl,
            $transformedUrl,
            $userSession
        );

        return $simpleResult["success"] ? $simpleResult["composition_url"] : $transformedUrl;
    }
}
