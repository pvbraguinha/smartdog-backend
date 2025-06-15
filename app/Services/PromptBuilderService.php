<?php

namespace App\Services;

class PromptBuilderService
{
    private $fallbackPrompt = "A hyper-realistic portrait of a 25-year-old human with subtle animal-inspired features. DSLR quality, cinematic background.";

    // Mapeamento padrão para raças (em inglês)
    private $racasIngles = [
        'vira-lata' => 'mixed-breed',
        'siames' => 'Siamese',
        'husky' => 'Husky',
        'labrador' => 'Labrador Retriever',
        // ...adicione mais conforme necessário
    ];

    // Mapeamento para tipo de pelagem
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

    // Formata o prompt final com base nas entradas do usuário
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
}
