<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class PetHumanController extends Controller
{
    public function upload(Request $request)
    {
        $request->validate([
            'focinho' => 'required|image|max:5120',
            'frontal' => 'required|image|max:5120',
            'angulo'  => 'required|image|max:5120',
        ]);

        $paths = [
            'focinho' => $request->file('focinho')->store('uploads/meupethumano/focinhos', 'public'),
            'frontal' => $request->file('frontal')->store('uploads/meupethumano/frontais', 'public'),
            'angulo'  => $request->file('angulo')->store('uploads/meupethumano/angulos', 'public'),
        ];

        return response()->json([
            'message' => 'Imagens recebidas com sucesso!',
            'paths' => $paths,
            'mock_human_image' => asset('mock/pethuman.jpg') // Pode colocar uma imagem fictícia por enquanto
        ]);
    }
}
