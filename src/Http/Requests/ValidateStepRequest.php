<?php

namespace Actengage\Wizard\Http\Requests;

use Laravel\Nova\Http\Requests\NovaRequest;

class ValidateStepRequest extends NovaRequest
{
    public $model;

    public $resource;

    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize()
    {
        $this->model = $this->resourceId ? $this->findModelQuery()->firstOrFail() : $this->model();

        $this->resource = $this->newResourceWith($this->model);

        $this->merge([
            'editing' => true,
            'editMode' => $this->model->exists ? 'update' : 'create'
        ]);

        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        return [];
    }
}
