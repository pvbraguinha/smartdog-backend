use Illuminate\Support\Facades\Storage;

public function upload(Request $request)
{
    $request->validate([
        'focinho' => 'required|file|image|max:5120',
        'frontal' => 'required|file|image|max:5120',
        'angulo'  => 'required|file|image|max:5120',
    ]);

    $paths = [
        'focinho' => Storage::disk('public')->put('uploads/meupethumano/focinhos', $request->file('focinho')),
        'frontal' => Storage::disk('public')->put('uploads/meupethumano/frontais', $request->file('frontal')),
        'angulo'  => Storage::disk('public')->put('uploads/meupethumano/angulos', $request->file('angulo')),
    ];

    return response()->json([
        'message' => 'Imagens recebidas com sucesso!',
        'paths' => $paths,
        'mock_human_image' => asset('mock/pethuman.jpg') // Trocar futuramente pela saída real da IA
    ]);
}
