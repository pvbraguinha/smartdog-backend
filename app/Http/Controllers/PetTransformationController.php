<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class PetTransformationController extends Controller
{
    public function transform(Request $request)
    {
        $request->validate([
            'frontal' => 'required|image',
            'focinho' => 'nullable|image',
            'breed'   => 'required|string',
            'session' => 'required|string',
        ]);

        $petImages = [];

        $allowedTypes = [
            'frontal' => 'frontal',
            'focinho' => 'focinho',
        ];

        foreach ($allowedTypes as $field => $folder) {
            if ($request->hasFile($field)) {
                $path = $request->file($field)->store("uploads/meupethumano/{$folder}", 's3');
                Storage::disk('s3')->setVisibility($path, 'public');
                $petImages[$field] = Storage::disk('s3')->url($path);
            }
        }

        return response()->json([
            'success' => true,
            'message' => 'Contribuição recebida com sucesso.',
            'uploaded_images' => $petImages,
        ]);
    }
}
