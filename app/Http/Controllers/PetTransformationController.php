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
            'angulo'  => 'nullable|image',
            'breed'   => 'required|string',
            'session' => 'required|string',
        ]);

        $petImages = [];

        $allowedTypes = [
            'frontal' => 'frontais',
            'focinho' => 'focinhos',
            'angulo'  => 'angulos',
        ];

        foreach ($allowedTypes as $field => $folder) {
            if ($request->hasFile($field)) {
                $path = $request->file($field)->store("uploads/meupethumano/{$folder}", 's3');
                Storage::disk('s3')->setVisibility($path, 'public');
                $petImages[$field] = Storage::disk('s3')->url($path);
            }
        }

        $result = $this->transformationService->transformPet(
            $petImages,
            $request->input('session'),
            $request->input('breed')
        );

        return response()->json($result);
    }
}
