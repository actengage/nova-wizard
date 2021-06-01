<?php

namespace Actengage\Wizard\Http\Controllers;

use Illuminate\Routing\Controller;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Laravel\Nova\Fields\FieldCollection;
use Laravel\Nova\Http\Requests\NovaRequest;

class ValidateStepController extends Controller
{
    public function handle(NovaRequest $request)
    { 
        if($request->resourceId) {
            $resource = $request->newResourceWith(
                $model = $request->findModelQuery()->firstOrFail()
            );

            $resource::validateForUpdate($request, $resource);
            $resource->fillForUpdate($request, $model);
        }
        else {
            $resource = $request->newResource();
            $resource::validateForCreation($request);
            $resource->fill($request, $model = $resource->model());
        }

        return $resource;
    }
}