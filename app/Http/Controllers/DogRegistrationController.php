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
        Log::info('📥 Iniciando registro de novo animal...');

        try {
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

            Log::info('✅ Dados validados com sucesso.', $validated);

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
                        Log::warning('⚠️ base64_decode falhou.');
                        return response()->json([
                            'success' => false,
                            'message' => 'Erro ao decodificar a imagem enviada.'
                        ], 400);
                    }

                    $filename = 'focinhos-smartdog/' . now()->format('Y-m-d') . '/' . Str::random(15) . '.jpg';
                    Storage::disk('s3')->put($filename, $imageData, 'public');
                    $photoUrl = Storage::disk('s3')->url($filename);

                    Log::info('📸 Imagem salva com sucesso no S3:', ['url' => $photoUrl]);

                } catch (\Exception $e) {
                    Log::error('❌ Erro ao salvar imagem base64 no S3: ' . $e->getMessage());

                    return response()->json([
                        'success' => false,
                        'message' => 'Erro ao salvar a imagem no servidor.',
                        'error' => $e->getMessage()
                    ], 500);
                }
            }

            try {
                $dogData = [
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
                ];

                Log::info('📦 Dados enviados para Dog::create():', $dogData);

                $dog = Dog::create($dogData);

                Log::info('✅ Cão registrado com sucesso no banco de dados.', ['dog_id' => $dog->id]);

                return response()->json([
                    'success' => true,
                    'message' => 'Animal registrado com sucesso!',
                    'data' => $dog
                ]);
            } catch (\Exception $e) {
                Log::error('❌ Falha ao criar o registro no banco: ' . $e->getMessage());

                return response()->json([
                    'success' => false,
                    'message' => 'Erro ao salvar os dados do animal no banco.',
                    'error' => $e->getMessage()
                ], 500);
            }

        } catch (\Throwable $e) {
            Log::error('🐛 ERRO GERAL NO REGISTRO DE CÃO: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Erro interno ao registrar o animal.',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
