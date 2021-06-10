<?php

namespace Actengage\Wizard;

use Illuminate\Contracts\Database\Eloquent\Castable;
use Illuminate\Contracts\Database\Eloquent\CastsAttributes;

class RequestDataCastable implements Castable {

    /**
     * Get the name of the caster class to use when casting from / to this cast target.
     *
     * @param  array  $arguments
     * @return string
     */
    public static function castUsing(array $arguments)
    {
        return new class implements CastsAttributes
        {
            /**
             * Transform the attribute from the underlying model values.
             *
             * @param  \Illuminate\Database\Eloquent\Model  $model
             * @param  string  $key
             * @param  mixed  $value
             * @param  array  $attributes
             * @return mixed
             */
            public function get($model, string $key, $value, array $attributes)
            {
                $args = array_values(json_decode($value, true) ?? [
                    'input' => null,
                    'files' => null
                ]);
                
                return new RequestData($model, ...$args);
            }

            /**
             * Transform the attribute to its underlying model values.
             *
             * @param  \Illuminate\Database\Eloquent\Model  $model
             * @param  string  $key
             * @param  mixed  $value
             * @param  array  $attributes
             * @return mixed
             */
            public function set($model, string $key, $value, array $attributes)
            {
                return json_encode($value->jsonSerialize());
            }
        };
    }

}