<?php

namespace Actengage\Wizard\Http\Controllers;

use Actengage\Wizard\Http\Requests\ValidateStepRequest;
use Illuminate\Routing\Controller;
use Laravel\Nova\Panel;

class ValidateStepController extends Controller
{
    public function handle(ValidateStepRequest $request)
    { 
        return !$request->resourceId
             ? $this->validateCreateRequest($request)
             : $this->validateUpdateRequest($request);
    }

    protected function validateCreateRequest(ValidateStepRequest $request)
    {
        $request->resource::validateForCreation($request, $request->resource);
        $request->resource->fill($request, $request->model);

        return response()->json([
            'fields' => $request->resource->creationFieldsWithinPanels($request, $request->resource),
            'panels' => $request->resource->availablePanelsForCreate($request, $request->resource),
        ]);
    }

    protected function validateUpdateRequest(ValidateStepRequest $request)
    {
        $request->resource::validateForUpdate($request, $request->resource);
        $request->resource->fillForUpdate($request, $request->model);

        return response()->json([
            'fields' => $request->resource->updateFieldsWithinPanels($request, $request->resource),
            'panels' => $request->resource->availablePanelsForUpdate($request, $request->resource),
        ]);
    }
}