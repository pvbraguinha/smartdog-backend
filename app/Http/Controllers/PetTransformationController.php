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
            'especie' => 'required|string',   // NOVO: campo obrigatório!
            'sex'     => 'required|string',   // NOVO: campo obrigatório!
            'age'     => 'required|string',   // NOVO: campo obrigatório!
            'session' => 'required|string',
        ]);

        $petImages = [];

        $allowedTypes = [
            'frontal' => 'frontal',
            'focinho' => 'focinho',
            'angulo'  => 'angulo',
        ];

        foreach ($allowedTypes as $field => $folder) {
            if ($request->hasFile($field)) {
                $path = $request->file($field)->store("uploads/meupethumano/{$folder}", 's3');
                Storage::disk('s3')->setVisibility($path, 'public');
                $petImages[$field] = Storage::disk('s3')->url($path);
            }
        }

        // Agora envia todos os campos corretamente!
        $result = $this->transformationService->transformPet(
            $petImages,
            $request->input('session'),
            $request->input('especie'),   // <-- Adicionado!
            $request->input('breed'),     // Raça
            $request->input('sex'),       // <-- Adicionado!
            $request->input('age')        // <-- Adicionado!
        );

        return response()->json($result);
    }
}
