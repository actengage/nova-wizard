<?php

namespace Actengage\Wizard\Http\Middleware;

use Actengage\Wizard\HasMultipleSteps;
use Actengage\Wizard\Http\Controllers\ValidateStepController;
use Closure;
use Illuminate\Http\JsonResponse;
use throwable;
use Illuminate\Http\Request;
use Illuminate\Routing\Router;
use Laravel\Nova\Http\Controllers\CreationFieldController;
use Laravel\Nova\Http\Controllers\ResourceStoreController;
use Laravel\Nova\Http\Controllers\ResourceUpdateController;
use Laravel\Nova\Http\Controllers\UpdateFieldController;
use Laravel\Nova\Http\Requests\NovaRequest;
use Laravel\Nova\Resource;

class AttachHeadersToResponse
{
    /**
     * The resource found in the request.
     * 
     * @var \Laravel\Nova\Resource
     */
    protected $resource;

    /**
     * Handle the request.
     * 
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     */
    public function handle(Request $request, Closure $next)
    {
        // Get the response and throw any errors before continuing.
        $response = $next($request);

        // Check for anything other than a JsonResponse and return it.
        if(!($response instanceof JsonResponse)) {
            return $response;
        }

        // Convert the request to a nova request instance.
        $novaRequest = NovaRequest::createFrom($request);

        // Check to see if the headers need to be attached.
        if($this->shouldAttachHeaders($novaRequest)) {
            if(!$this->resource) {
                $this->resource = $novaRequest->newResource();
            }
            
            // Attach the headers to the response
            $this->attachHeadersToResponse(
                $this->resource, $novaRequest, $response
            );

            // Get the model from the resource.
            $model = $this->resource->model();
            
            if(app('wizard.session')->isDirty()) {
                $model = $this->resource->model();

                // Merge the request back to the session and save.
                app('wizard.session')
                    ->merge($novaRequest)
                    ->associateModel($model)
                    ->associateUser(auth()->user())
                    ->save();
                
                // Restore the session back into the request instance.
                app('wizard.session')->restore($request);
            }
        }

        return $response;
    }

    /**
     * Attach the headers to the response.
     * 
     * @param  \Laravel\Nova\Resource  $resource
     * @param  \Laravel\Nova\Http\Requests\NovaRequest  $request
     * @param  \Illuminate\Http\Response|\Illuminate\Http\JsonResponse  $response
     */
    protected function attachHeadersToResponse(Resource $resource, NovaRequest $request, $response)
    {
        $response->header(config('wizard.session.header'), app('wizard.session')->id);
        $response->header('wizard-current-step', $resource->currentStep($request));
        $response->header('wizard-total-steps', $resource->totalSteps($request));
        
        if($this->checkControllerClass($request, [
            CreationFieldController::class,
            UpdateFieldController::class,
        ])) {
            $data = $response->getData();
            $data->steps = $this->resource
                ->steps($request)
                ->resolve($this->resource);

            $response->setData($data);
        }
    }

    /**
     * Check if the request is creating or updating a resource.
     * 
     * @param  \Illuminate\Http\Request  $request
     * @return bool
     */
    protected function checkControllerClass(Request $request, array $hastack = null)
    {
        $controller = app(Router::class)
            ->getRoutes()
            ->match($request)
            ->getController();

        return in_array(get_class($controller), $hastack);
    }

    /**
     * Attach the headers to the response.
     * 
     * @param  \Illuminate\Http\Request  $request
     * @param  bool
     */
    protected function shouldAttachHeaders(Request $request): bool
    {
        try {
            if($this->checkControllerClass($request, [
                ResourceStoreController::class,
                ResourceUpdateController::class,
                CreationFieldController::class,
                UpdateFieldController::class,
                ValidateStepController::class
            ])) {
                $this->resource = $request->resourceId
                    ? $request->findResourceOrFail($request->resourceId)
                    : $request->newResource();

                if(in_array(HasMultipleSteps::class, class_uses_recursive($this->resource))) {
                    return true;
                }
            }
        }
        catch(throwable $e) {
            // Ignore the exception...
        }
        
        return false;
    }
}