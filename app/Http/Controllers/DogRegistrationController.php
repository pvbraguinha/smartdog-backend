<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Log;
use App\Models\Dog;

class DogRegistrationController extends Controller
{
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'age' => 'nullable|string|max:50',
            'gender' => 'nullable|in:macho,femea',
            'breed' => 'nullable|string|max:100',
            'owner_name' => 'required|string|max:255',
            'phone' => 'required|string|max:20',
            'email' => 'nullable|email|max:255',
            'photo_base64' => 'nullable|string'
        ]);

        $photoUrl = null;

        if (!empty($validated['photo_base64'])) {
            try {
                $base64String = $validated['photo_base64'];

                // Remove prefixo se existir (ex: data:image/jpeg;base64,...)
                if (str_starts_with($base64String, 'data:image')) {
                    $base64String = preg_replace('/^data:image\/\w+;base64,/', '', $base64String);
                }

                $imageData = base64_decode($base64String);

                if ($imageData === false) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Erro ao decodificar a imagem enviada.'
                    ], 400);
                }

                $filename = 'focinhos-smartdog/' . now()->format('Y-m-d') . '/' . Str::random(15) . '.jpg';
                Storage::disk('s3')->put($filename, $imageData, 'public');
                $photoUrl = Storage::disk('s3')->url($filename);

            } catch (\Exception $e) {
                Log::error('Erro ao salvar imagem base64 no S3: ' . $e->getMessage());

                return response()->json([
                    'success' => false,
                    'message' => 'Erro ao salvar a imagem no servidor.',
                    'error' => $e->getMessage()
                ], 500);
            }
        }

        $dog = Dog::create([
            'name' => $validated['name'],
            'age' => $validated['age'] ?? null,
            'gender' => $validated['gender'] ?? null,
            'breed' => $validated['breed'] ?? null,
            'owner_name' => $validated['owner_name'],
            'phone' => $validated['phone'],
            'email' => $validated['email'] ?? null,
            'photo_url' => $photoUrl,
            'status' => 'em_casa',
            'show_phone' => true,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Animal registrado com sucesso!',
            'data' => $dog
        ]);
    }
}
