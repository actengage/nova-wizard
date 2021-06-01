<?php

namespace Actengage\Wizard;

use Illuminate\Support\Collection;
use Laravel\Nova\Fields\Field;
use Laravel\Nova\Fields\FieldCollection;
use Laravel\Nova\Resource;

class StepCollection extends Collection {

    /**
     * Get all the fields as single collection
     * 
     * @return \Laravel\Nova\Fields\FieldCollection
     */
    public function fields(): FieldCollection
    {
        // Extract the field collections frmo the steps.
        return $this->reduce(function($collection, Step $step) {
            return $collection->merge($step->fields());
        }, new FieldCollection);
    }

    /**
     * Fill all the fields in the steps.
     * 
     * @param  \Laravel\Nova\Resource  $resource
     * @return $this
     */
    public function resolve(Resource $resource): self
    {
        $this->fields()->each(function(Field $field) use ($resource) {
            $field->resolve($resource);
        });

        return $this;
    }

}
