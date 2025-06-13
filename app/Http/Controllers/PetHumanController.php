use App\Services\PetTransformationService;

class PetHumanController extends Controller
{
    protected $transformationService;

    public function __construct(PetTransformationService $transformationService)
    {
        $this->transformationService = $transformationService;
    }

    public function upload(Request $request)
    {
        try {
            $arquivos = $request->allFiles();
            $session = $request->input('session');
            $breed = $request->input('breed');

            Log::info('🧪 Arquivos recebidos:', array_keys($arquivos));

            $tipos = ['focinho', 'frontal', 'angulo'];
            $paths = [];
            $urls = [];
            $errors = [];

            foreach ($tipos as $tipo) {
                if (!isset($arquivos[$tipo])) {
                    continue;
                }

                $file = $arquivos[$tipo];
                if (!$file->isValid()) {
                    $errors[$tipo] = 'Arquivo inválido.';
                    continue;
                }

                $diretorio = "uploads/meupethumano/{$tipo}s";
                $path = Storage::disk('s3')->putFile($diretorio, $file);
                $paths[$tipo] = $path;
                $urls[$tipo] = Storage::disk('s3')->url($path); // Para usar com a IA
            }

            if (!isset($urls['frontal'])) {
                return response()->json([
                    'success' => false,
                    'error' => 'Imagem frontal é obrigatória.'
                ], 400);
            }

            // 🚀 Chama o serviço de transformação
            $result = $this->transformationService->transformPet($urls, $session, $breed);

            return response()->json($result);
        } catch (\Exception $e) {
            Log::error("❌ Erro geral no upload/transformação: " . $e->getMessage(), [
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
