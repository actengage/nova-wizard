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
                if($item instanceof Panel) {
                    return $carry->merge(array_filter($item->data, function($item) {
                        return $item instanceof Field;
                    }));
                }

                if($item instanceof Field) {
                    return $carry->merge([$item]);
                }

                return $carry;
            }, new FieldCollection);
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
