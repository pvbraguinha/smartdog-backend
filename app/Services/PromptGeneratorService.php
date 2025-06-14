<?php

namespace App\Services;

class PromptGeneratorService
{
    private $racasIngles = [
        // ... [mesmo dicionário de raças, sem alteração]
        // (mantenha sua lista aqui, igual você já fez)
    ];

    public function generate($especie, $raca, $sexo = null, $idade = null): string
    {
        $especie = strtolower(trim($especie));
        $raca = strtolower(trim($raca));
        $sexo = $sexo ? strtolower($sexo) : "male";
        $idadeStr = $idade ? "{$idade} years old" : "";

        // Tradução da espécie para inglês
        $animalEn = ($especie == 'gato' || $especie == 'gata') ? 'cat' : 'dog';

        // Detecta SRD/vira-lata/etc
        $srdLabels = ['srd', 'sem raça definida', 'vira-lata', '', null];

        // Decide raça final (traduz para inglês se possível)
        if (in_array($raca, $srdLabels, true)) {
            $racaPrompt = "mixed breed $animalEn";
        } else {
            $racaEn = $this->racasIngles[$raca] ?? ucfirst($raca);
            $racaPrompt = "$racaEn $animalEn";
        }

        // Array de estilos para sortear aleatoriamente
        $styles = [
            // Gângster moderno
            "wearing a gray hoodie, gold chain, streetwear, urban city background with graffiti, moody cinematic lighting, confident and intimidating expression, gangster vibe",
            // Playboy/Riquinho
            "wearing a designer jacket, luxury watch, neon city lights, elegant modern background, sophisticated and rich vibe, playboy style",
            // Rapper/Famoso
            "snapback cap, hoodie, music studio background, rapper style, cool and modern attitude, energetic vibe",
            // Marvel/Cinema
            "hooded costume, city skyline at night, heroic and epic expression, cinematic look, superhero character, dramatic shadows",
        ];
        $chosenStyle = $styles[array_rand($styles)];

        // Monta prompt final
        $prompt = "Ultra-realistic digital portrait of a young human with anthropomorphic features of a {$racaPrompt}: dog nose, fur, but human eyes and face. ";
        $prompt .= $chosenStyle;
        $prompt .= ", half-human half-dog, humanized, photorealistic, digital art, trending on Artstation, concept art";
        if ($sexo) $prompt .= ", {$sexo}";
        if ($idadeStr) $prompt .= ", {$idadeStr}";

        return $prompt;
    }
}
