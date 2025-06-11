use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;

public function upload(Request $request)
{
    $request->validate([
        'focinho' => 'required|image|max:5120',
        'frontal' => 'required|image|max:5120',
        'angulo'  => 'required|image|max:5120',
    ]);

    $paths = [];

    foreach (['focinho', 'frontal', 'angulo'] as $tipo) {
        $diretorio = "uploads/meupethumano/{$tipo}s";

        try {
            if (!Storage::disk('public')->exists($diretorio)) {
                Storage::disk('public')->makeDirectory($diretorio, 0755, true);
            }

            $paths[$tipo] = Storage::disk('public')->putFile($diretorio, $request->file($tipo));

            Log::info("✔️ {$tipo} salvo em: " . $paths[$tipo]);

        } catch (\Exception $e) {
            Log::error("❌ Erro ao salvar {$tipo}: " . $e->getMessage());
            return response()->json([
                'error' => "Falha ao salvar imagem de {$tipo}.",
                'exception' => $e->getMessage(),
            ], 500);
        }
    }

    return response()->json([
        'message' => 'Imagens recebidas com sucesso!',
        'paths' => $paths,
        'mock_human_image' => asset('mock/pethuman.jpg'),
    ]);
}
