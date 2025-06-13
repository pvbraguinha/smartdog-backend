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

    // Agora aceita breed, sex, age
    public function transformPet($petImages, $userSession, $manualBreed, $sex = null, $age = null)
    {
        try {
            $detectedBreed = $manualBreed;

            if (empty($detectedBreed)) {
                throw new \Exception("A raça do pet deve ser fornecida pelo usuário.");
            }
            if (empty($sex) || empty($age)) {
                throw new \Exception("Sexo e idade do pet são obrigatórios.");
            }

            Log::info("Raça fornecida pelo usuário: {$detectedBreed}");

            $prompt = $this->promptGenerator->generate($detectedBreed);

            Log::info("Prompt gerado", ["prompt" => $prompt]);

            $controlImageUrl = $this->getControlImageUrl($petImages);

            Log::info('🐾 Imagem de controle enviada para Replicate:', ['url' => $controlImageUrl]);

            if (empty($controlImageUrl)) {
                throw new \Exception("Nenhuma imagem frontal foi enviada para a transformação.");
            }

            $replicateResult = $this->replicate->transformPetToHuman($controlImageUrl, $prompt);

            if (!$replicateResult["success"]) {
                throw new \Exception("Falha na transformação: " . $replicateResult["error"]);
            }

            // Salva tudo junto no histórico!
            $this->updateTransformationHistory($userSession, $detectedBreed, $replicateResult, $sex, $age);

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
                "processing_time" => $replicateResult["processing_time"],
                "breed" => $detectedBreed,
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

    // Agora aceita $sex e $age
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

