<?php

namespace App\Services;

class PromptGeneratorService
{
    private $racasIngles = [
        // ... (mesmo dicionário de raças)
    ];

    // Conversão idade animal -> humana
    private function idadeHumana($animal, $idade)
    {
        if ($animal === 'dog') {
            return $idade * 7;
        }
        if ($animal === 'cat') {
            if ($idade == 1) return 15;
            if ($idade == 2) return 24;
            if ($idade > 2) return 24 + (($idade - 2) * 4);
        }
        return $idade;
    }

    public function generate($especie, $raca, $sexo = null, $idade = null): string
    {
        $especie = strtolower(trim($especie));
        $raca = strtolower(trim($raca));
        $sexo = $sexo ? strtolower($sexo) : "male";
        $animalEn = ($especie == 'gato' || $especie == 'gata') ? 'cat' : 'dog';
        $idadeHumana = $idade ? $this->idadeHumana($animalEn, (int)$idade) : null;
        $idadeStr = $idadeHumana ? "{$idadeHumana} years old" : "";

        $srdLabels = ['srd', 'sem raça definida', 'vira-lata', '', null];
        if (in_array($raca, $srdLabels, true)) {
            $racaPrompt = "mixed breed $animalEn";
        } else {
            $racaEn = $this->racasIngles[$raca] ?? ucfirst($raca);
            $racaPrompt = "$racaEn $animalEn";
        }

        $styles = [
            "urban streetwear, confident attitude, background full of graffiti, Instagram-style, Gen Z fashion",
            "luxury suit, modern penthouse, influencer, Miami vibes, millionaire lifestyle",
            "hoodie, gold chain, posing like a rapper, moody lighting, viral on TikTok",
            "leather jacket, motorbike, cinematic lighting, rebel look, Netflix series style",
            "hipster style, beanie hat, trendy beard, coffee shop background, social media influencer",
            "elegant dress, party look, red carpet style, paparazzi flashes, trending on Instagram",
        ];
        $chosenStyle = $styles[array_rand($styles)];

        // NOVO prompt, focando para humano real:
        $prompt = "Ultra realistic hyper-detailed digital portrait of a HUMAN person with subtle features inspired by a {$racaPrompt}, ";
        $prompt .= "FACE SHOULD BE FULLY HUMAN, only very subtle animal hints like fur texture, nose shape or eye color. ";
        $prompt .= "The portrait should look like a real influencer, not a furry or anthropomorphic animal. ";
        $prompt .= "The person is {$sexo}, {$idadeStr}. ";
        $prompt .= "Style: {$chosenStyle}. ";
        $prompt .= "Photography, studio lighting, skin pores, facial expression, depth of field, masterpiece, trending on ArtStation, shot by Annie Leibovitz, editorial Vogue magazine.";

        return $prompt;
    }
}
