<?php

namespace Actengage\Wizard;

use Laravel\Nova\Fields\Field;
use Laravel\Nova\Fields\FieldCollection;
use Laravel\Nova\Panel;

class Step extends Panel
{
    /**
     * The data array.
     * 
     * @return array
     */
    public $data;

    /**
     * Display the step as a panel on the details page.
     * 
     * @return bool
     */
    public $displayAsPanel = false;
    
    /**
     * Construct the step.
     * 
     * @param  string|null  $name
     * @param  array  $data
     */
    public function __construct(?string $name, array $data = [])
    {
        $this->name = $name;
        $this->data = $data;
    }

    /**
     * Get a collection of fields.
     * 
     * @return Laravel\Nova\Fields\FieldCollection
     */
    public function fields(): FieldCollection
    {
        return collect($this->data)
            ->reduce(function($carry, $item) {
                return $this->mergeFields($carry, $item);
            }, new FieldCollection)
            ->filter(function(Field $item) {
                return $item->showOnCreation || $item->showOnUpdate;
            });
    }

    /**
     * Merge the data recursively into the collection.
     * 
     * @param  Laravel\Nova\Fields\FieldCollection  $collection
     * @param  Laravel\Nova\Fields\Field|Laravel\Nova\Panel  $data
     * @return Laravel\Nova\Fields\FieldCollection
     */
    protected function mergeFields(FieldCollection $collection, $data): FieldCollection
    {
        if($data instanceof Field) {
            return $collection->merge([$data]);
        }

        if($data instanceof Panel) {
            foreach($data as $item) {
                $this->mergeFields($collection, $item);        
            }
        }

        return $collection;
    }

    /**
     * Display the step as a panel.
     * 
     * @param  bool  $value
     * @return $this
     */
    public function displayAsPanel(bool $value = true)
    {
        $this->displayAsPanel = $value;
        $this->prepareFields($this->data);

        return $this;
    }

    public function jsonSerialize()
    {
        return array_merge(parent::jsonSerialize(), [
            'name' => $this->name,
            'fields' => $this->fields()
        ]);
    }
}
