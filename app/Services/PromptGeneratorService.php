<?php

namespace App\Services;

class PromptGeneratorService
{
    private $racasIngles = [
        // ... sua lista de raças
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

        $racaEn = $this->racasIngles[$raca] ?? ucfirst($raca ?: 'dog');

        return "A hyper-realistic portrait of a {$idadeHumana}-year-old {$genero} inspired by a {$racaEn} dog, with subtle canine facial features such as nose and fur texture integrated into a human face. She is wearing a stylish outfit inspired by rapper style, confident expression, symmetrical lighting, DSLR quality, cinematic background.";
    }

    public function generateNegativePrompt(): string
    {
        // Nenhum negative prompt será usado para DALL·E
        return '';
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
