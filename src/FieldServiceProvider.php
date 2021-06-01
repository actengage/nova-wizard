<?php

namespace Actengage\Wizard;

use Actengage\Wizard\Http\Middleware\SetHeadersForJsonResponse;
use Illuminate\Contracts\Http\Kernel;
use Illuminate\Http\Request;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Route;
use Laravel\Nova\Events\ServingNova;
use Laravel\Nova\Nova;

class FieldServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap any application services.
     *
     * @return void
     */
    public function boot(Kernel $kernel)
    {        
        $kernel->pushMiddleware(SetHeadersForJsonResponse::class);

        $this->app->booted(function () {
            $this->routes();
        });
        
        Nova::serving(function (ServingNova $event) {
            Nova::script('wizard', __DIR__.'/../dist/js/field.js');
        });
    }

    /**
     * Register any application services.
     *
     * @return void
     */
    public function register()
    {
        //
    }

    /**
     * Register the tool's routes.
     *
     * @return void
     */
    protected function routes()
    {
        if ($this->app->routesAreCached()) {
            return;
        }

        Route::middleware(['nova'])
            ->namespace('\\Actengage\\Wizard\\Http\\Controllers')
            ->prefix('nova-vendor/wizard')
            ->group(__DIR__.'/../routes/api.php');
    }
}
