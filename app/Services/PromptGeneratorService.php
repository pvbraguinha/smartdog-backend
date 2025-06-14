<?php

namespace App\Services;

class PromptGeneratorService
{
    private $racasIngles = [
        // ... sua lista original de raças
    ];

    public function generate($especie, $raca, $sexo = null, $idade = null): string
    {
        $especie = strtolower(trim($especie));
        $raca = strtolower(trim($raca));
        $sexo = $sexo ? strtolower($sexo) : "male";

        // 🧠 Converte idade animal textual ("10 dias", "3 meses", "2 anos") em anos decimais
        $idadeEmAnos = $this->converterIdadeParaAnos($idade);
        $idadeHumana = $idadeEmAnos;

        // Aplica fator de conversão
        if (in_array($especie, ['cachorro', 'cão', 'cao', 'dog'])) {
            $idadeHumana = round($idadeEmAnos * 7);
        } elseif (in_array($especie, ['gato', 'gata', 'cat'])) {
            $idadeHumana = round($idadeEmAnos * 6);
        }

        $idadeStr = $idade ? "{$idadeHumana} years old" : "";

        // Tradução da espécie
        $animalEn = ($especie == 'gato' || $especie == 'gata') ? 'cat' : 'dog';

        // Raça
        $srdLabels = ['srd', 'sem raça definida', 'vira-lata', '', null];
        if (in_array($raca, $srdLabels, true)) {
            $racaPrompt = "mixed breed $animalEn";
        } else {
            $racaEn = $this->racasIngles[$raca] ?? ucfirst($raca);
            $racaPrompt = "$racaEn $animalEn";
        }

        $styles = [
            "wearing a gray hoodie, gold chain, streetwear, urban city background with graffiti, moody cinematic lighting, confident and intimidating expression, gangster vibe",
            "wearing a designer jacket, luxury watch, neon city lights, elegant modern background, sophisticated and rich vibe, playboy style",
            "snapback cap, hoodie, music studio background, rapper style, cool and modern attitude, energetic vibe",
            "hooded costume, city skyline at night, heroic and epic expression, cinematic look, superhero character, dramatic shadows",
        ];
        $chosenStyle = $styles[array_rand($styles)];

        $prompt = "Ultra-realistic digital portrait of a young human with anthropomorphic features of a {$racaPrompt}: dog nose, fur, but human eyes and face. ";
        $prompt .= $chosenStyle;
        $prompt .= ", half-human half-dog, humanized, photorealistic, digital art, trending on Artstation, concept art";
        if ($sexo) $prompt .= ", {$sexo}";
        if ($idadeStr) $prompt .= ", {$idadeStr}";

        return $prompt;
    }

    public function generateNegativePrompt(): string
    {
        return implode(', ', [
            "animal snout",
            "animal ears",
            "fur",
            "furry",
            "non-human nose",
            "dog nose",
            "cat face",
            "blurred face",
            "extra limbs",
            "mutated body",
            "deformed anatomy",
            "non-human eyes",
            "zombie",
            "creature",
            "bad anatomy",
            "low quality",
            "cartoon",
            "monstrous"
        ]);
    }

    private function converterIdadeParaAnos(?string $idade): float
    {
        if (!$idade) return 0;

        $idade = strtolower(trim($idade));
        $idade = str_replace(['de ', 'aproximadamente '], '', $idade); // limpa entrada

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

        // Se não conseguir detectar, tenta converter direto
        return is_numeric($idade) ? floatval($idade) : 0;
    }
}

