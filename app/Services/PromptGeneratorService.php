<?php

namespace App\Services;

class PromptGeneratorService
{
    private $racasIngles = [
        // ... [mantenha seu dicionário de raças aqui]
    ];

    public function generate($especie, $raca, $sexo = null, $idade = null): array
    {
        $especie = strtolower(trim($especie));
        $raca = strtolower(trim($raca));
        $sexo = $sexo ? strtolower($sexo) : "male";

        // Tradução da espécie para inglês
        $animalEn = ($especie == 'gato' || $especie == 'gata') ? 'cat' : 'dog';

        // Conversão de idade animal para idade humana
        $idadeHumana = null;
        if ($idade && is_numeric($idade)) {
            if ($animalEn == 'dog') {
                $idadeHumana = $idade * 7;
            } else {
                $idadeHumana = $idade * 6;
            }
        }
        $idadeStr = $idadeHumana ? "{$idadeHumana} year old human" : "";

        // Detecta SRD/vira-lata/etc
        $srdLabels = ['srd', 'sem raça definida', 'vira-lata', '', null];

        // Decide raça final (traduz para inglês se possível)
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

        // PROMPT positivo
        $prompt = "Ultra-realistic digital portrait of a young human with anthropomorphic features of a {$racaPrompt}: ";
        $prompt .= "dog nose, fur, but human eyes and face, human hair, realistic skin, no animal mouth or animal proportions. ";
        $prompt .= $chosenStyle;
        $prompt .= ", half-human half-{$animalEn}, humanized, photorealistic, digital art, trending on Artstation, concept art";
        if ($sexo) $prompt .= ", {$sexo}";
        if ($idadeStr) $prompt .= ", {$idadeStr}";

        // PROMPT negativo — para evitar "animal demais" na imagem
        $negativePrompt = "cartoon, illustration, painting, low quality, animal face, dog face, cat face, animal nose, muzzle, snout, extra legs, extra arms, fangs, paws, animal mouth, ugly, deformed, blurry, distortion, bad anatomy, bad proportions, animal ears";

        return [
            'prompt' => $prompt,
            'negative_prompt' => $negativePrompt
        ];
    }
}

