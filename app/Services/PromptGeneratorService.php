<?php

namespace App\Services;

class PromptGeneratorService
{
    /**
     * Gera o prompt de forma direta (usado internamente)
     */
    public function generate(string $breed, string $gender = 'male', int $age = 25): string
    {
        $styles = ['wearing a hoodie', 'with stylish glasses', 'in a colorful scarf', 'in urban streetwear'];
        $backgrounds = ['urban background', 'studio lighting', 'cozy room', 'nature field'];

        $description = match (strtolower($breed)) {
            'yorkshire' => 'small face, long silky golden-brown hair, bright eyes',
            'pitbull' => 'muscular build, short gray hair, strong jaw, deep-set eyes',
            'golden retriever' => 'soft face, kind eyes, golden wavy hair',
            default => 'features inspired by a ' . $breed,
        };

        $style = $styles[array_rand($styles)];
        $background = $backgrounds[array_rand($backgrounds)];

        return "A {$age}-year-old {$gender} human inspired by a {$breed}: {$description}, {$style}, {$background}, DSLR quality";
    }

    /**
     * Gera um prompt completo com variação de idade e gênero
     */
    public function generateUniquePrompt(string $breed, string $session): array
    {
        $gender = ['male', 'female'][rand(0, 1)];
        $age = rand(20, 35);

        return [
            'prompt' => $this->generate($breed, $gender, $age),
            'gender' => $gender,
            'age' => $age
        ];
    }
}
