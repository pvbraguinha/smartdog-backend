<?php

namespace App\Services;

class PromptBuilderService
{
    private $fallbackPrompt = "A hyper-realistic portrait of a 25-year-old human with subtle animal-inspired features. DSLR quality, cinematic background.";

    private $racasIngles = [
        'vira-lata' => 'mixed-breed',
        'siames' => 'Siamese',
        'husky' => 'Husky',
        'labrador' => 'Labrador Retriever',
        // ...adicione mais conforme necessário
    ];

    private $pelagemCores = [
        'branca' => 'white-furred',
        'preta' => 'black-furred',
        'caramelo' => 'caramel-furred',
        'laranja' => 'orange-furred',
        'cinza' => 'gray-furred',
        'rajada' => 'striped-furred',
        'tricolor' => 'tricolor-furred',
        'manchada' => 'spotted-furred'
    ];

    public function gerarPrompt($idade, $sexo, $raca, $especie, $pelagem): string
    {
        try {
            $genero = strtolower($sexo) === 'fêmea' || strtolower($sexo) === 'female' ? 'female' : 'male';
            $idadeTexto = is_numeric($idade) ? "{$idade}-year-old {$genero}" : "young {$genero}";
            $especieEn = strtolower($especie) === 'gato' || strtolower($especie) === 'cat' ? 'cat' : 'dog';
            $racaEn = $this->racasIngles[strtolower($raca)] ?? ucfirst($raca);
            $pelagemEn = $this->pelagemCores[strtolower($pelagem)] ?? 'white-furred';

            return "A hyper-realistic portrait of a {$idadeTexto} inspired by a {$pelagemEn} {$racaEn} {$especieEn}, with subtle {$especieEn}-like facial features such as nose and fur texture integrated into a human face. The person is wearing a stylish outfit inspired by rapper style, confident expression, symmetrical lighting, DSLR quality, cinematic background.";

        } catch (\Exception $e) {
            return $this->fallbackPrompt;
        }
    }

    // ✅ Adicionado para evitar o erro de método indefinido
    public function calcularIdadeHumana(string $especie, string $idade): int
    {
        $idadeEmAnos = $this->converterIdadeParaAnos($idade);

        if (in_array(strtolower($especie), ['cachorro', 'cao', 'cão', 'dog'])) {
            return round($idadeEmAnos * 7);
        } elseif (in_array(strtolower($especie), ['gato', 'gata', 'cat'])) {
            return round($idadeEmAnos * 6);
        }

        return round($idadeEmAnos);
    }

    private function converterIdadeParaAnos(?string $idade): float
    {
        if (!$idade) return 0;

        $idade = strtolower(trim($idade));
        $idade = str_replace(['de ', 'aproximadamente '], '', $idade);

        if (str_contains($idade, 'dia')) {
            preg_match('/(\d+)/', $idade, $matches);
            return isset($matches[1]) ? floatval($matches[1]) / 365 : 0;
        }

        if (str_contains($idade, 'mes') || str_contains($idade, 'mês')) {
            preg_match('/(\d+)/', $idade, $matches);
            return isset($matches[1]) ? floatval($matches[1]) / 12 : 0;
        }

        if (str_contains($idade, 'ano')) {
            preg_match('/(\d+)/', $idade, $matches);
            return isset($matches[1]) ? floatval($matches[1]) : 0;
        }

        return is_numeric($idade) ? floatval($idade) : 0;
    }
}
