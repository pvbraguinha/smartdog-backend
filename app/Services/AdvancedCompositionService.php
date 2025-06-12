<?php
// app/Services/AdvancedCompositionService.php

namespace App\Services;

use Intervention\Image\ImageManagerStatic as Image;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;

class AdvancedCompositionService extends ImageCompositionService
{
    /**
     * Cria composição com template profissional
     */
    public function createProfessionalComposition($originalImageUrl, $transformedImageUrl, $breedName, $userSession)
    {
        try {
            // Dimensões do template profissional
            $templateWidth = 1200;
            $templateHeight = 800;
            $imageSize = 350;
            
            // Criar canvas com gradiente
            $canvas = $this->createGradientBackground($templateWidth, $templateHeight);
            
            // Baixar e processar imagens
            $originalImage = $this->downloadAndProcessImage($originalImageUrl, \"original\");
            $transformedImage = $this->downloadAndProcessImage($transformedImageUrl, \"transformed\");
            
            // Criar molduras circulares
            $originalCircular = $this->createCircularImage($originalImage, $imageSize);
            $transformedCircular = $this->createCircularImage($transformedImage, $imageSize);
            
            // Posicionar imagens
            $leftX = 150;
            $rightX = $templateWidth - $imageSize - 150;
            $centerY = ($templateHeight - $imageSize) / 2;
            
            $canvas->insert($originalCircular, \"top-left\", $leftX, $centerY);
            $canvas->insert($transformedCircular, \"top-left\", $rightX, $centerY);
            
            // Adicionar seta de transformação
            $this->addTransformationArrow($canvas, $leftX + $imageSize, $rightX, $centerY + $imageSize/2);
            
            // Adicionar textos
            $this->addProfessionalTexts($canvas, $breedName, $templateWidth, $templateHeight);
            
            // Adicionar logo/marca d\'água
            $this->addWatermark($canvas, $templateWidth, $templateHeight);
            
            // Salvar
            $compositionUrl = $this->saveCompositionToS3($canvas, $userSession, \"professional\");
            
            return [
                \"success\" => true,
                \"composition_url\" => $compositionUrl,
                \"template\" => \"professional\",
                \"width\" => $templateWidth,
                \"height\" => $templateHeight
            ];
            
        } catch (\Exception $e) {
            Log::error(\"Erro na composição profissional: \" . $e->getMessage());
            return [
                \"success\" => false,
                \"error\" => $e->getMessage()
            ];
        }
    }

    private function createGradientBackground($width, $height)
    {
        $canvas = Image::canvas($width, $height);
        
        // Simular gradiente com retângulos
        for ($i = 0; $i < $height; $i++) {
            $opacity = 1 - ($i / $height) * 0.3;
            $color = sprintf(\"rgba(240, 248, 255, %.2f)\", $opacity);
            
            $canvas->rectangle(0, $i, $width, $i + 1, function ($draw) use ($color) {
                $draw->background($color);
            });
        }
        
        return $canvas;
    }

    private function createCircularImage($image, $size)
    {
        // Redimensionar mantendo proporção
        $resized = $this->resizeImageMaintainAspect($image, $size);
        
        // Criar máscara circular
        $mask = Image::canvas($size, $size, \"transparent\");
        $mask->circle($size - 10, $size/2, $size/2, function ($draw) {
            $draw->background(\"#ffffff\");
        });
        
        // Aplicar máscara
        $circular = Image::canvas($size, $size, \"transparent\");
        $circular->insert($resized, \"center\");
        $circular->mask($mask, false);
        
        // Adicionar borda
        $circular->circle($size - 5, $size/2, $size/2, function ($draw) {
            $draw->border(3, \"#ffffff\");
        });
        
        return $circular;
    }

    private function addTransformationArrow($canvas, $startX, $endX, $centerY)
    {
        $arrowY = $centerY;
        $arrowLength = $endX - $startX - 20;
        $arrowStartX = $startX + 10;
        $arrowEndX = $endX - 10;
        
        // Linha principal
        $canvas->line($arrowStartX, $arrowY, $arrowEndX, $arrowY, function ($draw) {
            $draw->color(\"#4CAF50\");
            $draw->width(4);
        });
        
        // Ponta da seta
        $canvas->line($arrowEndX - 15, $arrowY - 8, $arrowEndX, $arrowY, function ($draw) {
            $draw->color(\"#4CAF50\");
            $draw->width(4);
        });
        
        $canvas->line($arrowEndX - 15, $arrowY + 8, $arrowEndX, $arrowY, function ($draw) {
            $draw->color(\"#4CAF50\");
            $draw->width(4);
        });
    }

    private function addProfessionalTexts($canvas, $breedName, $width, $height)
    {
        // Título principal
        $canvas->text(\"Transformação Pet → Humano\", $width/2, 80, function($font) {
            $font->size(32);
            $font->color(\"#2c3e50\");
            $font->align(\"center\");
            $font->valign(\"middle\");
        });
        
        // Subtítulo com raça
        $breedFormatted = ucwords(str_replace(\"_\", \" \", $breedName));
        $canvas->text(\"Raça: {$breedFormatted}\", $width/2, 120, function($font) {
            $font->size(20);
            $font->color(\"#7f8c8d\");
            $font->align(\"center\");
            $font->valign(\"middle\");
        });
        
        // Labels das imagens
        $canvas->text(\"Seu Pet\", 325, $height - 50, function($font) {
            $font->size(18);
            $font->color(\"#34495e\");
            $font->align(\"center\");
        });
        
        $canvas->text(\"Versão Humana\", $width - 325, $height - 50, function($font) {
            $font->size(18);
            $font->color(\"#34495e\");
            $font->align(\"center\");
        });
    }

    private function addWatermark($canvas, $width, $height)
    {
        // Adicionar marca d\'água discreta
        $canvas->text(\"PetHuman.AI\", $width - 20, $height - 20, function($font) {
            $font->size(12);
            $font->color(\"rgba(0,0,0,0.3)\");
            $font->align(\"right\");
            $font->valign(\"bottom\");
        });
    }
}
