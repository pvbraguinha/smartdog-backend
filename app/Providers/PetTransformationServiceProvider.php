<?php
// app/Providers/PetTransformationServiceProvider.php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use App\Services\PromptBuilderService;
use App\Services\DalleService;
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
        $this->app->singleton(PromptBuilderService::class);
        $this->app->singleton(DalleService::class);
        $this->app->singleton(ImageCompositionService::class);
        $this->app->singleton(AdvancedCompositionService::class);

        $this->app->singleton(PetTransformationService::class, function ($app) {
            return new PetTransformationService(
                $app->make(PromptBuilderService::class),
                $app->make(DalleService::class),
                $app->make(ImageCompositionService::class)
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

