<?php

namespace Someline\Image;


use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Support\ServiceProvider;
use Someline\Models\Image\SomelineImage;

class SomelineImageServiceProvider extends ServiceProvider
{

    /**
     * Perform post-registration booting of services.
     *
     * @return void
     */
    public function boot()
    {
        Relation::morphMap([
            SomelineImage::MORPH_NAME => SomelineImage::class,
        ]);
        $this->loadMigrationsFrom(__DIR__ . '/../../migrations');
        $this->publishes([
            __DIR__ . '/../../config/config.php' => config_path('someline-image.php'),

            // master files
            __DIR__ . '/../../master/SomelineImage.php.dist' => app_path('Models/Image/SomelineImage.php'),

        ]);
    }

    /**
     * Register the service provider.
     *
     * @return void
     */
    public function register()
    {
        $this->mergeConfigFrom(
            __DIR__ . '/../../config/config.php',
            'someline-image'
        );
    }
}