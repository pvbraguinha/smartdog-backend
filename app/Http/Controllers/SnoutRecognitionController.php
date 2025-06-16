<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Dog;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class SnoutRecognitionController extends Controller
{
    public function detect(Request $request)
    {
        $validated = $request->validate([
            'image' => 'required|image'
        ]);

        // Gera nome com data e salva na pasta do dia
        $dateFolder = now()->format('Y-m-d');
        $filename = "focinhos/{$dateFolder}/" . Str::random(15) . '.' . $request->file('image')->getClientOriginalExtension();

        Storage::disk('s3')->put($filename, file_get_contents($request->file('image')), 'public');

        // Simulação de reconhecimento
        $recognized = rand(0, 1) === 1;

        if ($recognized) {
            $dog = Dog::find(1);

            if (!$dog) {
                return response()->json([
                    'success' => false,
                    'message' => 'Cão simulado (ID 1) não encontrado no banco.'
                ], 404);
            }

            return response()->json([
                'success' => true,
                'dog_id' => $dog->id,
                'name' => $dog->name,
                'status' => $dog->status,
                'phone' => $dog->status === 'perdido' ? $dog->phone : null,
                'message' => 'Focinho reconhecido com sucesso.',
                'image_url' => Storage::disk('s3')->url($filename),
            ]);
        }

        return response()->json([
            'success' => false,
            'message' => 'Focinho não detectado. Tente novamente.'
        ], 404);
    }
}
