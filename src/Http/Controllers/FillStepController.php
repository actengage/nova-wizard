<?php

namespace Actengage\Wizard\Http\Controllers;

use Illuminate\Routing\Controller;
use Laravel\Nova\Http\Requests\NovaRequest;

class FillStepController extends Controller
{
    public function handle(NovaRequest $request)
    { 
        if($request->resourceId) {
            $resource = $request->newResourceWith(
                $model = $request->findModelQuery()->firstOrFail()
            );

            $resource->fillForUpdate($request, $model);
        }
        else {
            $resource = $request->newResource();
            $resource->fill($request, $model = $resource->model());
        }

        return $resource;
    }
}