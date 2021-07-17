<?php

namespace Actengage\Wizard;

use Actengage\Wizard\Console\Commands\ClearExpiredSessions;
use Actengage\Wizard\Http\Controllers\ValidateStepController;
use Actengage\Wizard\Http\Middleware\AttachHeadersToResponse;
use Actengage\Wizard\Http\Requests\ValidateStepRequest;
use Illuminate\Contracts\Http\Kernel;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;
use Illuminate\Routing\Router;
use Laravel\Nova\Events\NovaServiceProviderRegistered;
use Laravel\Nova\Http\Controllers\CreationFieldController;
use Laravel\Nova\Http\Controllers\UpdateFieldController;
use Laravel\Nova\Http\Requests\CreateResourceRequest;
use Laravel\Nova\Http\Requests\NovaRequest;
use Laravel\Nova\Nova;
use League\Flysystem\Filesystem;

class FieldServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap any application services.
     *
     * @return void
     */
    public function boot(Kernel $kernel)
    {     
        /*
        $request = Request::createFromBase(request());

        if(app('wizard.session')->restoreIfExists($request)) {
            request()->query = $request->query;
            request()->files = $request->files;
        }
        */

        $kernel->pushMiddleware(AttachHeadersToResponse::class);

        $this->bootOnlyForConsole();
    }

    /**
     * Register any application services.
     *
     * @return void
     */
    public function register()
    {
        $this->registerFilesystemDisk();

        Nova::booted(function($event) {
            $this->registerWizardSession();
            $this->registerRoutes();

            Nova::serving(function() {
                $this->bootedAndServing();
            });
        });

        if (!$this->app->configurationIsCached()) {
            $this->mergeConfigFrom(
                __DIR__.'/../config/wizard.php', 'wizard'
            );
        }
        
        $this->loadViewsFrom(__DIR__.'/../resources/views', 'wizard');
    }

    /**
     * Register the routes.
     *
     * @return void
     */
    protected function registerRoutes()
    {
        if($this->app->routesAreCached()) {
            return;
        }

        Route::middleware(['nova'])
            ->prefix('nova-vendor/wizard')
            ->namespace('\\Actengage\\Wizard\\Http\\Controllers')
            ->group(__DIR__.'/../routes/api.php');
    }

    /**
     * After Nova has booted and has began serving.
     *
     * @return void
     */
    protected function bootedAndServing()
    {
        Nova::script('wizard', __DIR__.'/../dist/js/field.js');
    }

    /**
     * Register a local file disk for the wizard sessions.
     * 
     * @return void;
     */
    protected function registerFilesystemDisk()
    {
        $adapter = $this->app->get('filesystem')->createLocalDriver([
            'root' => storage_path('wizard'),
            'permissions' => [
                'file' => [
                    'public' => 0664,
                    'private' => 0600,
                ],
                'dir' => [
                    'public' => 0775,
                    'private' => 0700,
                ],
            ]
        ]);
        
        $this->app->get('filesystem')->set('wizard', $adapter);

        $this->app->singleton('wizard.filesystem', function() use ($adapter) {
            return $adapter;
        });
        
        $this->app->singleton('wizard.disk', function() {
            $this->app->get('filesystem')->disk(config('wizard.disk'));    
        });
    }

    /**
     * Register the wizard session.
     * 
     * @return void
     */
    protected function registerWizardSession()
    {
        $this->app->singleton('wizard.session.id', function($app) {
            return config('wizard.session.model')::id();
        });

        $this->app->singleton('wizard.session', function($app) {
            return config('wizard.session.model')::request();
        });
    }

    /**
     * Boot only for the console.
     * 
     * @return void
     */
    protected function bootOnlyForConsole()
    {
        if(!$this->app->runningInConsole()) {
            return;
        }

        $this->commands([
            ClearExpiredSessions::class
        ]);

        $this->loadMigrationsFrom(__DIR__.'/../database/migrations');
        
        $this->publishes([
            __DIR__.'/../config/wizard.php' => config_path('wizard.php'),
        ]);
    }
}
