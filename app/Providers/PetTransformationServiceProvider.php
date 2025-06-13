<?php
// app/Providers/PetTransformationServiceProvider.php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use App\Services\PromptGeneratorService;
use App\Services\ReplicateService;
use App\Services\ImageCompositionService;
use App\Services\AdvancedCompositionService;
use App\Services\PetTransformationService;

class PetTransformationServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        $this->app->singleton(PromptGeneratorService::class);
        $this->app->singleton(ReplicateService::class);
        $this->app->singleton(ImageCompositionService::class);
        $this->app->singleton(AdvancedCompositionService::class);

        $this->app->singleton(PetTransformationService::class, function ($app) {
            return new PetTransformationService(
                $app->make(PromptGeneratorService::class),
                $app->make(ReplicateService::class),
                $app->make(ImageCompositionService::class) // Injetando o serviço de composição
            );
        });
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        // Nenhuma ação necessária no boot por enquanto
    }
}
