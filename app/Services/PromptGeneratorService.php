<?php

namespace App\Services;

class PromptGeneratorService
{
    private $racasIngles = [
        // GATOS
        'siamês' => 'Siamese',
        'persa' => 'Persian',
        'maine coon' => 'Maine Coon',
        'angorá' => 'Turkish Angora',
        'sphynx' => 'Sphynx',
        'ragdoll' => 'Ragdoll',
        'azul russo' => 'Russian Blue',
        'bengal' => 'Bengal',
        'chartreux' => 'Chartreux',
        'sagrado da birmânia' => 'Birman',
        'birmanês' => 'Burmese',
        'oriental' => 'Oriental Shorthair',
        'abissínio' => 'Abyssinian',
        'american shorthair' => 'American Shorthair',
        'scottish fold' => 'Scottish Fold',
        'exótico' => 'Exotic Shorthair',
        'manx' => 'Manx',
        'somali' => 'Somali',
        'balinês' => 'Balinese',
        'bombaim' => 'Bombay',
        'norueguês da floresta' => 'Norwegian Forest',
        'himaláia' => 'Himalayan',
        'bobtail japonês' => 'Japanese Bobtail',
        'cornish rex' => 'Cornish Rex',
        'devon rex' => 'Devon Rex',
        'burmilla' => 'Burmilla',
        'turkish van' => 'Turkish Van',

        // CÃES
        'vira-lata' => 'mixed breed',
        'srd' => 'mixed breed',
        'sem raça definida' => 'mixed breed',
        'golden retriever' => 'Golden Retriever',
        'labrador' => 'Labrador Retriever',
        'labrador retriever' => 'Labrador Retriever',
        'pastor alemão' => 'German Shepherd',
        'pastor belga' => 'Belgian Shepherd',
        'bulldog francês' => 'French Bulldog',
        'bulldog inglês' => 'English Bulldog',
        'beagle' => 'Beagle',
        'poodle' => 'Poodle',
        'yorkshire' => 'Yorkshire Terrier',
        'pinscher' => 'Pinscher',
        'rottweiler' => 'Rottweiler',
        'dachshund' => 'Dachshund',
        'teckel' => 'Dachshund',
        'shih tzu' => 'Shih Tzu',
        'shihtzu' => 'Shih Tzu',
        'lhasa apso' => 'Lhasa Apso',
        'chihuahua' => 'Chihuahua',
        'schnauzer' => 'Schnauzer',
        'maltês' => 'Maltese',
        'maltes' => 'Maltese',
        'boxer' => 'Boxer',
        'border collie' => 'Border Collie',
        'pitbull' => 'Pitbull',
        'american staffordshire' => 'American Staffordshire Terrier',
        'dobermann' => 'Doberman Pinscher',
        'doberman' => 'Doberman Pinscher',
        'cocker spaniel' => 'Cocker Spaniel',
        'dalmatian' => 'Dalmatian',
        'jack russell' => 'Jack Russell Terrier',
        'husky siberiano' => 'Siberian Husky',
        'husky' => 'Siberian Husky',
        'akita' => 'Akita',
        'bulldog' => 'Bulldog',
        'bulldog americano' => 'American Bulldog',
        'pastor australiano' => 'Australian Shepherd',
        'pug' => 'Pug',
        'basset hound' => 'Basset Hound',
        'cão de crista chinês' => 'Chinese Crested Dog',
        'cavalier king charles' => 'Cavalier King Charles Spaniel',
        'west highland white terrier' => 'West Highland White Terrier',
        'fox terrier' => 'Fox Terrier',
        'greyhound' => 'Greyhound',
        'irish setter' => 'Irish Setter',
        'kerry blue terrier' => 'Kerry Blue Terrier',
        'pomerânia' => 'Pomeranian',
        'pomeranian' => 'Pomeranian',
        'lulu da pomerânia' => 'Pomeranian',
        'pointer' => 'Pointer',
        'pequinês' => 'Pekingese',
        'pekingese' => 'Pekingese',
        'weimaraner' => 'Weimaraner',
        'whippet' => 'Whippet',
        'samoyeda' => 'Samoyed',
        'samoyed' => 'Samoyed',
        'saint bernard' => 'Saint Bernard',
        'são bernardo' => 'Saint Bernard',
        'shar pei' => 'Shar Pei',
        'skye terrier' => 'Skye Terrier',
        'spitz alemão' => 'German Spitz',
        'staffordshire bull terrier' => 'Staffordshire Bull Terrier',
        'terrier brasileiro' => 'Brazilian Terrier',
        'buldogue campeiro' => 'Brazilian Bulldog',
        'fila brasileiro' => 'Fila Brasileiro',
        'pastor suíço' => 'White Swiss Shepherd',
        'pastor branco suíço' => 'White Swiss Shepherd',
        'pastor maremmano-abruzzese' => 'Maremma Sheepdog',
        'pastor de shetland' => 'Shetland Sheepdog',
        'corgi' => 'Welsh Corgi',
        'corgi galês' => 'Welsh Corgi',
        'dogo argentino' => 'Dogo Argentino',
        'bull terrier' => 'Bull Terrier',
        'chow chow' => 'Chow Chow',
        'collie' => 'Collie',
        'fox paulistinha' => 'Brazilian Terrier',
        'great dane' => 'Great Dane',
        'dogue alemão' => 'Great Dane',
        'italian greyhound' => 'Italian Greyhound',
        'newfoundland' => 'Newfoundland',
        'pekingese' => 'Pekingese',
        'ridgeback' => 'Rhodesian Ridgeback',
        'saluki' => 'Saluki',
        'shiba inu' => 'Shiba Inu',
        'spaniel' => 'Spaniel',
        'vizsla' => 'Vizsla',
        'wolfspitz' => 'Keeshond',
        // ...adicione quantos mais quiser!
    ];

    public function generate($especie, $raca, $sexo = null, $idade = null): string
    {
        $especie = strtolower(trim($especie));
        $raca = strtolower(trim($raca));
        $sexo = $sexo ? strtolower($sexo) : null;

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

        // Monta prompt
        $prompt = "photo portrait of a $racaPrompt";
        if ($sexo) $prompt .= ", $sexo";
        if ($idade) $prompt .= ", $idade years old";
        $prompt .= ", ultra realistic, photoreal, full color";

        return $prompt;
    }
}
