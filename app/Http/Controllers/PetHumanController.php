use Illuminate\Support\Facades\Storage;

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

        // Cria o diretório se não existir
        if (!Storage::disk('public')->exists($diretorio)) {
            Storage::disk('public')->makeDirectory($diretorio);
        }

        // Salva o arquivo no diretório correto
        $paths[$tipo] = Storage::disk('public')->putFile($diretorio, $request->file($tipo));
    }

    return response()->json([
        'message' => 'Imagens recebidas com sucesso!',
        'paths' => $paths,
        'mock_human_image' => asset('mock/pethuman.jpg'),
    ]);
}
