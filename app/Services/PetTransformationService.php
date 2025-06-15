<?php

namespace App\Services;

use App\Models\TransformationHistory;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;

class PetTransformationService
{
    private $promptBuilder;
    private $dalle;
    private $imageCompositionService;

    public function __construct(
        PromptBuilderService $promptBuilder,
        DalleService $dalle,
        ImageCompositionService $imageCompositionService
    ) {
        $this->promptBuilder = $promptBuilder;
        $this->dalle = $dalle;
        $this->imageCompositionService = $imageCompositionService;
    }

    public function transformPet($petImages, $userSession, $especie, $manualBreed, $sex = null, $age = null, $pelagem = null)
    {
        try {
            if (empty($especie) || empty($manualBreed)) {
                throw new \Exception("Espécie e raça do pet devem ser fornecidas pelo usuário.");
            }
            if (empty($sex) || empty($age)) {
                throw new \Exception("Sexo e idade do pet são obrigatórios.");
            }

            Log::info("Transformação solicitada: {$especie}, {$manualBreed}, {$sex}, {$age}, pelagem: {$pelagem}");

            $idadeHumana = $this->promptBuilder->calcularIdadeHumana($especie, $age);
            $prompt = $this->promptBuilder->gerarPrompt(
                idade: $idadeHumana,
                sexo: $sex,
                raca: $manualBreed,
                especie: $especie,
                pelagem: $pelagem
            );

            Log::info("PROMPT FINAL USADO:", ['prompt' => $prompt]);

            $dalleImageUrl = $this->dalle->gerarImagemComPrompt($prompt);
            $controlImageUrl = $this->getControlImageUrl($petImages);

            $compositeImageUrl = $this->createSideBySideComposition(
                $controlImageUrl,
                $dalleImageUrl,
                $userSession
            );

            $this->updateTransformationHistory($userSession, $manualBreed, $dalleImageUrl, $sex, $age);

            return [
                "success" => true,
                "especie" => $especie,
                "breed_detected" => $manualBreed,
                "original_image" => $controlImageUrl,
                "transformed_image" => $dalleImageUrl,
                "composite_image" => $compositeImageUrl,
                "prompt_used" => $prompt,
                "processing_time" => "approx 8s",
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

    private function updateTransformationHistory($userSession, $breed, $outputUrl, $sex = null, $age = null)
    {
        $history = TransformationHistory::where("user_session", $userSession)
            ->where("breed_detected", $breed)
            ->latest()
            ->first();

        $data = [
            "replicate_prediction_id" => null,
            "result_image_url" => $outputUrl,
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
