<?php

namespace SmartDato\Labelary\Providers;

use Illuminate\Support\ServiceProvider;
use SmartDato\Labelary\Services\Labelary;

class LabelaryServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     *
     * @return void
     */
    public function register()
    {
        // The static methods work through the same singleton, so that options set
        // through the facade are applied to subsequent conversions
        $this->app->singleton('labelary', function ($app) {
            return Labelary::getInstance();
        });
    }

    /**
     * Register anything in the service container.
     *
     * @return void
     */
    public function boot()
    {
        $this->publishes([
            __DIR__.'/../../config/labelary.php' => config_path('labelary.php'),
        ], 'labelary-config');

        $this->mergeConfigFrom(
            __DIR__.'/../../config/labelary.php',
            'labelary'
        );
    }
}
