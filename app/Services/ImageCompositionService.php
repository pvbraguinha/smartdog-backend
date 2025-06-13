<?php
// app/Services/ImageCompositionService.php

namespace App\Services;

use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;
use Image; // Usando facade da versão 2

class ImageCompositionService
{
    protected $outputWidth = 1024;
    protected $outputHeight = 512;
    protected $imageSize = 512;

    /**
     * Cria composição lado a lado do pet original e versão humana.
     * Esse método é um alias e chama o método profissional.
     */
    public function createSideBySideComposition($originalImageUrl, $transformedImageUrl, $userSession)
    {
        // Para compatibilidade, apenas chama o principal
        return $this->createProfessionalComposition($originalImageUrl, $transformedImageUrl, $userSession);
    }

    /**
     * Cria composição lado a lado (profissional) do pet original e versão humana.
     */
    public function createProfessionalComposition($originalImageUrl, $transformedImageUrl, $userSession)
    {
        try {
            $original = $this->downloadAndProcessImage($originalImageUrl, "original");
            $transformed = $this->downloadAndProcessImage($transformedImageUrl, "transformed");

            $canvas = Image::canvas($this->outputWidth, $this->outputHeight, '#f8f9fa');

            $originalResized = $this->resizeImageMaintainAspect($original, $this->imageSize);
            $transformedResized = $this->resizeImageMaintainAspect($transformed, $this->imageSize);

            $leftX = ($this->imageSize - $originalResized->width()) / 2;
            $leftY = ($this->outputHeight - $originalResized->height()) / 2;
            $rightX = $this->imageSize + ($this->imageSize - $transformedResized->width()) / 2;
            $rightY = ($this->outputHeight - $transformedResized->height()) / 2;

            $canvas->insert($originalResized, 'top-left', (int)$leftX, (int)$leftY);
            $canvas->insert($transformedResized, 'top-left', (int)$rightX, (int)$rightY);

            // Divider line
            $canvas->line(
                $this->imageSize, 0,
                $this->imageSize, $this->outputHeight,
                function ($draw) {
                    $draw->color('#adb5bd');
                    $draw->width(2);
                }
            );

            // Labels
            $canvas->text("Original", $leftX + 10, $leftY + 20, function ($font) {
                $font->size(20);
                $font->color('#343a40');
                $font->align('left');
            });

            $canvas->text("Versão Humana", $rightX + 10, $rightY + 20, function ($font) {
                $font->size(20);
                $font->color('#343a40');
                $font->align('left');
            });

            // Marca d'água
            $canvas->text("smartdog", $this->outputWidth - 10, $this->outputHeight - 10, function ($font) {
                $font->size(12);
                $font->color('#ced4da');
                $font->align('right');
                $font->valign('bottom');
            });

            $compositionUrl = $this->saveCompositionToS3($canvas, $userSession);

            return [
                'success' => true,
                'composition_url' => $compositionUrl
            ];

        } catch (\Exception $e) {
            Log::error("Erro na composição profissional: " . $e->getMessage());
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    protected function downloadAndProcessImage($imageUrl, $type)
    {
        $content = file_get_contents($imageUrl);
        if (!$content) throw new \Exception("Erro ao baixar imagem {$type}");

        return Image::make($content)->sharpen(10);
    }

    protected function resizeImageMaintainAspect($image, $maxSize)
    {
        return $image->resize($maxSize, null, function ($constraint) {
            $constraint->aspectRatio();
            $constraint->upsize();
        });
    }

    protected function saveCompositionToS3($canvas, $userSession)
    {
        $filename = "compositions/{$userSession}_professional_" . time() . ".jpg";
        $imageData = $canvas->encode("jpg", 90);
        Storage::disk("s3")->put($filename, $imageData->__toString());
        return Storage::disk("s3")->url($filename);
    }
}

