<?php
// app/Services/ImageCompositionService.php

namespace App\Services;

use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;
use Intervention\Image\ImageManager;
use Intervention\Image\Drivers\Gd\Driver;

class ImageCompositionService
{
    protected $outputWidth = 1024;
    protected $outputHeight = 512;
    protected $imageSize = 512;
    protected $imageManager;

    public function __construct()
    {
        $this->imageManager = new ImageManager(new Driver());
    }

    public function createSideBySideComposition($originalImageUrl, $transformedImageUrl, $userSession)
    {
        try {
            $originalImage = $this->downloadAndProcessImage($originalImageUrl, 'original');
            $transformedImage = $this->downloadAndProcessImage($transformedImageUrl, 'transformed');

            $canvas = $this->imageManager->canvas($this->outputWidth, $this->outputHeight, '#ffffff');

            $originalResized = $this->resizeImageMaintainAspect($originalImage, $this->imageSize);
            $transformedResized = $this->resizeImageMaintainAspect($transformedImage, $this->imageSize);

            $leftX = ($this->imageSize - $originalResized->width()) / 2;
            $leftY = ($this->outputHeight - $originalResized->height()) / 2;

            $rightX = $this->imageSize + ($this->imageSize - $transformedResized->width()) / 2;
            $rightY = ($this->outputHeight - $transformedResized->height()) / 2;

            $canvas->insert($originalResized, 'top-left', $leftX, $leftY);
            $canvas->insert($transformedResized, 'top-left', $rightX, $rightY);

            $this->addDividerLine($canvas);
            $this->addLabels($canvas);

            $compositionUrl = $this->saveCompositionToS3($canvas, $userSession);

            return [
                'success' => true,
                'composition_url' => $compositionUrl,
                'width' => $this->outputWidth,
                'height' => $this->outputHeight
            ];
        } catch (\Exception $e) {
            Log::error("Erro na composição de imagens: " . $e->getMessage());
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    public function createMultipleVariationsComposition($originalImageUrl, $transformedImages, $userSession)
    {
        try {
            $variationCount = count($transformedImages);
            $gridCols = min(3, $variationCount + 1);
            $gridRows = ceil(($variationCount + 1) / $gridCols);

            $canvasWidth = $gridCols * $this->imageSize;
            $canvasHeight = $gridRows * $this->imageSize;

            $canvas = $this->imageManager->canvas($canvasWidth, $canvasHeight, '#ffffff');

            $originalImage = $this->downloadAndProcessImage($originalImageUrl, 'original');
            $originalResized = $this->resizeImageMaintainAspect($originalImage, $this->imageSize);
            $canvas->insert($originalResized, 'top-left', 0, 0);

            $position = 1;
            foreach ($transformedImages as $transformedUrl) {
                $row = floor($position / $gridCols);
                $col = $position % $gridCols;

                $x = $col * $this->imageSize;
                $y = $row * $this->imageSize;

                $transformedImage = $this->downloadAndProcessImage($transformedUrl, 'variation_' . $position);
                $transformedResized = $this->resizeImageMaintainAspect($transformedImage, $this->imageSize);

                $canvas->insert($transformedResized, 'top-left', $x, $y);
                $position++;
            }

            $compositionUrl = $this->saveCompositionToS3($canvas, $userSession, 'variations');

            return [
                'success' => true,
                'composition_url' => $compositionUrl,
                'width' => $canvasWidth,
                'height' => $canvasHeight,
                'grid' => ['cols' => $gridCols, 'rows' => $gridRows]
            ];
        } catch (\Exception $e) {
            Log::error("Erro na composição de variações: " . $e->getMessage());
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    protected function downloadAndProcessImage($imageUrl, $type)
    {
        try {
            $imageContent = file_get_contents($imageUrl);

            if (!$imageContent) {
                throw new \Exception("Falha ao baixar imagem: {$type}");
            }

            $image = $this->imageManager->read($imageContent);
            $image->sharpen(10);

            return $image;
        } catch (\Exception $e) {
            Log::error("Erro ao processar imagem {$type}: " . $e->getMessage());
            throw $e;
        }
    }

    protected function resizeImageMaintainAspect($image, $maxSize)
    {
        $width = $image->width();
        $height = $image->height();

        if ($width > $height) {
            $image->resize($maxSize, null, function ($constraint) {
                $constraint->aspectRatio();
                $constraint->upsize();
            });
        } else {
            $image->resize(null, $maxSize, function ($constraint) {
                $constraint->aspectRatio();
                $constraint->upsize();
            });
        }

        return $image;
    }

    protected function addDividerLine($canvas)
    {
        $canvas->line(
            $this->imageSize, 0,
            $this->imageSize, $this->outputHeight,
            function ($draw) {
                $draw->color('#cccccc');
                $draw->width(2);
            }
        );
    }

    protected function addLabels($canvas)
    {
        $canvas->text('Original', 10, 30, function ($font) {
            $font->size(20);
            $font->color('#333333');
            $font->align('left');
        });

        $canvas->text('Versão Humana', $this->imageSize + 10, 30, function ($font) {
            $font->size(20);
            $font->color('#333333');
            $font->align('left');
        });
    }

    protected function saveCompositionToS3($canvas, $userSession, $type = 'composition')
    {
        try {
            $filename = "compositions/{$userSession}_{$type}_" . time() . ".jpg";
            $imageData = $canvas->toJpeg(90)->toString();

            $path = Storage::disk('s3')->put($filename, $imageData);

            if (!$path) {
                throw new \Exception("Falha ao salvar composição no S3");
            }

            return Storage::disk('s3')->url($filename);
        } catch (\Exception $e) {
            Log::error("Erro ao salvar composição: " . $e->getMessage());
            throw $e;
        }
    }
}

