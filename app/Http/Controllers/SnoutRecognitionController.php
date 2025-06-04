<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Dog;

class SnoutRecognitionController extends Controller
{
    public function detect(Request $request)
    {
        $validated = $request->validate([
            'photo_base64' => 'required|string'
        ]);

        // Simulação de reconhecimento: 50% chance de sucesso
        $recognized = rand(0, 1) === 1;

        if ($recognized) {
            // Aqui você buscaria o dog real no banco por id
            // Vou deixar fixo para id 1 para exemplo
            $dog = Dog::find(1);

            return response()->json([
                'success' => true,
                'dog_id' => $dog->id,
                'name' => $dog->name,
                'status' => $dog->status,
                'phone' => $dog->status === 'perdido' ? $dog->phone : null,
                'message' => 'Focinho reconhecido com sucesso.'
            ]);
        }

        return response()->json([
            'success' => false,
            'message' => 'Focinho não detectado. Tente novamente.'
        ], 404);
    }
}