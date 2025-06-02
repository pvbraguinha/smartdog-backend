<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class SnoutRecognitionController extends Controller
{
    public function detect(Request $request)
    {
        if (!$request->hasFile('image')) {
            Log::warning('⚠️ Nenhuma imagem recebida no request');
            return response()->json([
                'success' => false,
                'message' => 'Nenhuma imagem enviada.',
            ], 400, ['Content-Type' => 'application/json']);
        }

        Log::info('📸 Imagem recebida com sucesso');

        // Simulando falha de reconhecimento para forçar o app a mostrar mensagem de erro
        return response()->json([
            'success' => false,
            'message' => 'Focinho não detectado. Tente novamente.'
        ], 200, ['Content-Type' => 'application/json']);
    }
}



