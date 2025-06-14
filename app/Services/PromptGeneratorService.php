<?php

namespace App\Services;

class PromptGeneratorService
{
    private $racasIngles = [
        // ... sua lista
    ];

    public function generate($especie, $raca, $sexo = null, $idade = null): string
    {
        $especie = strtolower(trim($especie));
        $raca = strtolower(trim($raca));
        $sexo = $sexo ? strtolower($sexo) : "male";

        $idadeEmAnos = $this->converterIdadeParaAnos($idade);
        $idadeHumana = $idadeEmAnos;

        if (in_array($especie, ['cachorro', 'cão', 'cao', 'dog'])) {
            $idadeHumana = round($idadeEmAnos * 7);
        } elseif (in_array($especie, ['gato', 'gata', 'cat'])) {
            $idadeHumana = round($idadeEmAnos * 6);
        }

        $idadeStr = $idade ? "{$idadeHumana}-year-old human" : "young adult";

        $animalEn = ($especie == 'gato' || $especie == 'gata') ? 'cat' : 'dog';
        $srdLabels = ['srd', 'sem raça definida', 'vira-lata', '', null];
        $racaEn = in_array($raca, $srdLabels, true)
            ? "mixed breed $animalEn"
            : ($this->racasIngles[$raca] ?? ucfirst($raca));

        return "A hyper-realistic portrait of a {$idadeStr} inspired by a {$racaEn}: DSLR quality, detailed face, smooth skin, confident expression, cinematic lighting, symmetrical features, natural background, urban style";
    }

    public function generateNegativePrompt(): string
    {
        return implode(', ', [
            "deformed",
            "mutated",
            "ugly",
            "extra limbs",
            "low quality",
            "blurry",
            "bad anatomy",
            "poorly drawn face",
            "asymmetry",
            "disfigured",
            "distorted",
            "cartoonish",
            "surreal",
            "animal face",
            "fur",
            "furry",
            "dog snout",
            "animal nose"
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

        if (str_contains($idade, 'mes')) {
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

