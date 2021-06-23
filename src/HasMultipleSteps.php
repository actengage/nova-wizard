<?php

namespace Actengage\Wizard;

use Actengage\Wizard\Http\Controllers\FillStepController;
use Actengage\Wizard\Http\Controllers\ValidateStepController;
use Illuminate\Routing\Router;
use Illuminate\Support\Collection;
use Laravel\Nova\Fields\FieldCollection;
use Laravel\Nova\Http\Controllers\CreationFieldController;
use Laravel\Nova\Http\Controllers\UpdateFieldController;
use Laravel\Nova\Http\Requests\NovaRequest;
use Laravel\Nova\Panel;
use Laravel\Nova\ResourceTool;

trait HasMultipleSteps
{
    /**
     * Get all of the resources's sessions.
     */
    public function sessions()
    {
        return $this->morphMany(Session::class, 'model');
    }

    /**
     * Get the fields that are available for the given request.
     *
     * @param  \Laravel\Nova\Http\Requests\NovaRequest  $request
     * @return \Laravel\Nova\Fields\FieldCollection
     */
    public function availableFields(NovaRequest $request)
    {        
        $method = $this->fieldsMethod($request);

        $fields = new FieldCollection($this->{$method}($request));
        
        $controller = app(Router::class)
            ->getRoutes()
            ->match($request)
            ->getController();

        switch(get_class($controller)) {
            case UpdateFieldController::class:
            case CreationFieldController::class:
            case FillStepController::class:
            case ValidateStepController::class:   
                // Extract steps from the fields.
                $steps = $this->extractSteps($request, $fields);
  
                // Get the current step instance from the collection.
                $step = $steps->get($this->currentStep($request) - 1);
        
                return $step->fields();
        }
        
        return $this->extractFields($fields);
    }

    /**
     * Get the current step.
     * 
     * @param  \Laravel\Nova\Http\Requests\NovaRequest  $request
     * @return int
     */
    public function currentStep(NovaRequest $request): int
    {
        return min((int) $request->input('step', 1), $this->totalSteps($request));
    }  
    
    /**
     * Remove all the steps in the collection.
     * 
     * @param  \Laravel\Nova\Fields\FieldCollection  $fields
     * @return \Laravel\Nova\Fields\FieldCollection
     */
    protected function extractFields(FieldCollection $fields): FieldCollection
    {
        return $fields->reduce(function($carry, $item) {
            if($item instanceof Step) {
                return $carry->merge(
                    $this->extractFields(new FieldCollection($item->data))
                );
            }

            if($item instanceof Panel) {
                return $carry->merge($item->data);
            }

            return $carry->merge([$item]);
        }, new FieldCollection);
    }
    
    /**
     * Extract a collection of panels from the defined fields.
     * 
     * @param  \Illuminate\Support\Collection  $fields
     * @return \Illuminate\Support\Collection
     */
    protected function extractPanels(Collection $fields): Collection
    {      
        return $fields->reduce(function($carry, $item) {
            if($item instanceof Step) {
                // If the items is a step, recursively extract the panels
                $carry = $carry->merge(
                    $this->extractPanels(collect($item->data))
                );

                // If the step should be displayed as a panel, merge it too.
                if($item->displayAsPanel) {
                    $carry = $carry->merge([$item]); 
                }

                return $carry;
            }

            return $carry->merge([$item]);
        }, collect())->whereInstanceOf(Panel::class)->values();
    }
    
    /**
     * Extract a collection of steps from the defined fields.
     * 
     * @param  \Laravel\Nova\Http\Requests\NovaRequest  $request
     * @param  \Laravel\Nova\Fields\FieldCollection  $fields
     * @return \Actenage\Wizard\StepCollection
     */
    protected function extractSteps(NovaRequest $request, FieldCollection $fields): StepCollection
    {
        $steps = new StepCollection($fields->filter(function($field) {
            return $field instanceof Step;
        }));

        $defaultFields = $fields->filter(function($field) {
            return !$field instanceof Step && !(
                $field instanceof ResourceTool
            );
        });

        if($defaultFields->count()) {
            $steps->prepend(new Step(null, $defaultFields));
        }        
        
        return $steps->filter(function(Step $step) use ($request) {
            if($request->editing && $request->editMode == 'create') {
                return !!$this->removeNonCreationFields(
                    $request, $step->fields()
                )->count();
            }
            else if($request->editing && $request->editMode == 'update') {
                return !!$this->removeNonUpdateFields(
                    $request, $step->fields()
                )->count();
            }

            return true;
        })->values();
    }

    /**
     * Return the panels for this request with the default label.
     *
     * @param  string  $label
     * @param  \Laravel\Nova\Http\Requests\NovaRequest  $request
     * @return array
     */
    protected function panelsWithDefaultLabel($label, NovaRequest $request)
    {
        $method = $this->fieldsMethod($request);

        return with(
            $this->extractPanels(collect(array_values($this->{$method}($request)))),
            function ($panels) use ($label) {
                return $panels->when($panels->where('name', $label)->isEmpty(), function ($panels) use ($label) {
                    return $panels->prepend((new Panel($label))->withToolbar());
                })->all();
            }
        );
    }
    
    /**
     * Get the steps for the resource.
     * 
     * @param  \Laravel\Nova\Http\Requests\NovaRequest  $request
     * @return \Actengage\Wizard\StepCollection
     */
    public function steps(NovaRequest $request): StepCollection
    {
        $method = $this->fieldsMethod($request);

        $fields = new FieldCollection($this->{$method}($request));

        // Get the available steps from the fields.
        return $this->extractSteps($request, $fields);
    }
    
    
    /**
     * Get the number of total steps for a resource.
     * 
     * @param  \Laravel\Nova\Http\Requests\NovaRequest  $request
     * @return int
     */
    public function totalSteps(NovaRequest $request): int
    {
        return $this->steps($request)->count();
    }

}