namespace App\Services;

use Illuminate\Support\Facades\Http;

class DalleService
{
    public function gerarImagem(string $prompt): string
    {
        $apiKey = env('OPENAI_API_KEY');

        $response = Http::withToken($apiKey)
            ->post('https://api.openai.com/v1/images/generations', [
                'model' => 'dall-e-3',
                'prompt' => $prompt,
                'n' => 1,
                'size' => '1024x1024',
                'response_format' => 'url',
            ]);

        if ($response->successful()) {
            return $response->json('data.0.url');
        }

        throw new \Exception('Erro ao gerar imagem: ' . $response->body());
    }
}
