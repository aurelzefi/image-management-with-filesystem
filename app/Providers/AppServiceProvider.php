<?php

namespace App\Providers;

use Intervention\Image\ImageManager;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     *
     * @return void
     */
    public function register()
    {
        $this->app->bind(ImageManager::class, function ($app) {
            return new ImageManager(['driver' => 'gd']);
        });
    }
}
