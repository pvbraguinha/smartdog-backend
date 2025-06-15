<?php

namespace App\Services;

class PromptGeneratorService
{
    private $racasIngles = [
        // ... sua lista de raças (mantenha como está)
    ];

    public function generate($especie, $raca, $sexo = null, $idade = null): string
    {
        $especie = strtolower(trim($especie));
        $raca = strtolower(trim($raca));
        $sexo = $sexo ? strtolower($sexo) : "male";

        $idadeEmAnos = $this->converterIdadeParaAnos($idade);
        if (in_array($especie, ['cachorro', 'cão', 'cao', 'dog'])) {
            $idadeHumana = round($idadeEmAnos * 7);
        } elseif (in_array($especie, ['gato', 'gata', 'cat'])) {
            $idadeHumana = round($idadeEmAnos * 6);
        } else {
            $idadeHumana = round($idadeEmAnos);
        }

        $genero = ($sexo === 'fêmea' || $sexo === 'femea' || $sexo === 'female') ? 'female' : 'male';
        $generoTexto = ($genero === 'female') ? 'woman' : 'man';
        $idadeTexto = $idadeHumana > 0 ? "{$idadeHumana}-year-old {$genero}" : "young adult {$genero}";

        $animalEn = ($especie == 'gato' || $especie == 'gata') ? 'cat' : 'dog';
        $srdLabels = ['srd', 'sem raça definida', 'vira-lata', '', null];
        $racaEn = in_array($raca, $srdLabels, true)
            ? "mixed breed $animalEn"
            : ($this->racasIngles[$raca] ?? ucfirst($raca));

        // 🎲 Estilos aleatórios
        $estilos = [
            'rapper style',
            'cyberpunk outfit',
            'medieval knight armor',
            'anime-inspired clothing',
            'veterinarian uniform',
            'dentist scrubs',
            'soccer player uniform',
            'military soldier gear',
            'luxury fashion model look',
            'streetwear with urban vibe'
        ];
        $estiloEscolhido = $estilos[array_rand($estilos)];

        return "A hyper-realistic portrait of a {$idadeTexto} inspired by a {$racaEn} {$animalEn}, with subtle {$animalEn}-like facial features such as nose shape and fur texture integrated into a human face. The {$generoTexto} is wearing a stylish outfit inspired by {$estiloEscolhido}, with confident expression, symmetrical face, expressive eyes, DSLR quality, and cinematic lighting.";
    }

    public function generateNegativePrompt(): string
    {
        return implode(', ', [
            "deformed", "mutated", "ugly", "extra limbs", "low quality", "blurry", "bad anatomy",
            "poorly drawn face", "asymmetry", "disfigured", "distorted", "cartoonish", "surreal",
            "animal face", "animal ears", "snout", "muzzle", "whiskers", "paws", "tail", "fur",
            "furry texture", "animal body", "dog face", "cat face", "dog snout", "cat snout",
            "animal nose", "animal eyes", "non-human eyes", "pet collar", "leash", "animal clothing",
            "objects on head", "text", "watermark", "signature", "logo", "painting", "drawing",
            "sketch", "illustration", "anime", "manga", "childish", "crying", "sad expression"
        ]);
    }

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
