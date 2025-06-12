<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Services\PetTransformationService;
use Illuminate\Support\Facades\Storage;

class PetTransformationController extends Controller
{
    protected $transformationService;

    public function __construct(PetTransformationService $transformationService)
    {
        $this->transformationService = $transformationService;
    }

    public function transform(Request $request)
    {
        $request->validate([
            'frontal' => 'required|image',
            'focinho' => 'nullable|image',
            'angulo' => 'nullable|image',
            'breed' => 'required|string',
            'session' => 'required|string',
        ]);

        // Armazenar as imagens no S3 e gerar URLs públicas
        $petImages = [];

        foreach (['frontal', 'focinho', 'angulo'] as $type) {
            if ($request->hasFile($type)) {
                $path = $request->file($type)->store("uploads/meupethumano/{$type}", 's3');
                Storage::disk('s3')->setVisibility($path, 'public');
                $petImages[$type] = Storage::disk('s3')->url($path);
            }
        }

        // Chamar o serviço de transformação
        $result = $this->transformationService->transformPet(
            $petImages,
            $request->input('session'),
            $request->input('breed')
        );

        return response()->json($result);
    }
}

