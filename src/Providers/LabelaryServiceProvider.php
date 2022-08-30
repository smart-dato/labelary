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
        $this->app->bind('labelary', function ($app) {
            return new Labelary();
        });
    }

    /**
     * Register anything in the service container.
     *
     * @return void
     */
    public function boot()
    {
        //
    }
}
