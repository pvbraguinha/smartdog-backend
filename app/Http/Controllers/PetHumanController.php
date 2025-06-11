<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;

class PetHumanController extends Controller
{
    public function upload(Request $request)
{
    try {
        Log::info('🧪 Iniciando upload sem validação...');
        
        // Salva só UM arquivo pra teste simples
        $path = Storage::disk('s3')->put('teste', $request->file('focinho'));
        
        return response()->json([
            'ok' => true,
            'path' => $path
        ]);
    } catch (\Exception $e) {
        Log::error('🔥 Erro de upload isolado: ' . $e->getMessage());
        return response()->json([
            'error' => $e->getMessage()
        ], 500);
    }
}

        return response()->json([
            'message' => 'Imagens recebidas e salvas no S3 com sucesso!',
            'paths' => $paths,              // caminhos no S3
            'temporary_urls' => $urls,      // URLs pré-assinadas
            'mock_human_image' => asset('mock/pethuman.jpg'), // imagem temporária de exemplo
        ]);
    }
}
