<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class PetHumanController extends Controller
{
    public function upload(Request $request)
    {
        $request->validate([
            'focinho' => 'required|image|max:5120',
            'frontal' => 'required|image|max:5120',
            'angulo'  => 'required|image|max:5120',
        ]);

        $basePath = public_path('storage/uploads/meupethumano');
        $paths = [];

        foreach (['focinho', 'frontal', 'angulo'] as $tipo) {
            $folder = "{$basePath}/{$tipo}s";

            if (!file_exists($folder)) {
                mkdir($folder, 0775, true);
            }

            $file = $request->file($tipo);
            $filename = uniqid() . '_' . $file->getClientOriginalName();
            $file->move($folder, $filename);

            $paths[$tipo] = asset("storage/uploads/meupethumano/{$tipo}s/{$filename}");
        }

        return response()->json([
            'message' => 'Imagens recebidas com sucesso!',
            'paths' => $paths,
            'mock_human_image' => asset('mock/pethuman.jpg'),
        ]);
    }
}
