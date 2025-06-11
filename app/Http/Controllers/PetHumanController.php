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
            
            // Salva apenas o arquivo "focinho" no bucket S3, dentro da pasta "teste"
            $path = Storage::disk('s3')->put('teste', $request->file('focinho'));

            Log::info("✔️ Upload de focinho realizado com sucesso em: " . $path);

            return response()->json([
                'ok' => true,
                'path' => $path
            ]);
        } catch (\Exception $e) {
            Log::error('🔥 Erro de upload isolado: ' . $e->getMessage(), ['trace' => $e->getTraceAsString()]);
            return response()->json([
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
