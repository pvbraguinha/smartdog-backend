<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class DogLocationController extends Controller
{
    public function update(Request $request, $id)
    {
        // Validação com mensagens personalizadas
        $validated = $request->validate([
            'status' => 'required|in:em_casa,perdido,em_busca_de_tutor',
            'show_phone' => 'boolean'
        ], [
            'status.required' => 'O status é obrigatório.',
            'status.in' => 'Status inválido. Escolha entre: em_casa, perdido ou em_busca_de_tutor.',
            'show_phone.boolean' => 'O campo show_phone deve ser verdadeiro ou falso.'
        ]);

        $status = $validated['status'];
        $showPhone = $validated['show_phone'] ?? false;

        Log::info("📍 Atualizando status do cão ID $id para: $status, telefone visível: " . ($showPhone ? 'sim' : 'não'));

        return response()->json([
            'success' => true,
            'dog_id' => $id,
            'new_status' => $status,
            'show_phone' => $showPhone,
            'message' => 'Status atualizado com sucesso.'
        ]);
    }
}


