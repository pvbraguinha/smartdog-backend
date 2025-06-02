<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

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
            'photo' => 'nullable|image|max:5120'
        ], [
            'name.required' => 'O nome do animal é obrigatório.',
            'owner_name.required' => 'O nome do proprietário é obrigatório.',
            'phone.required' => 'O telefone é obrigatório.',
            'gender.in' => 'O gênero deve ser "macho" ou "femea".',
            'photo.image' => 'A foto deve ser uma imagem válida.'
        ]);

        Log::info('🐶 Novo animal registrado:', $validated);

        return response()->json([
            'success' => true,
            'message' => 'Animal registrado com sucesso (simulado)',
            'data' => $validated
        ]);
    }
}
