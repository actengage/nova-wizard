<?php

namespace Actengage\Wizard;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

class Session extends Model {

    /**
     * Does the model increment the primary key.
     * 
     * @var bool
     */
    public $incrementing = false;

    /**
     * The primary key data type.
     * 
     * @var string
     */
    public $keyType = 'string';

    /**
     * The database table.
     * 
     * @var string
     */
    protected $table = 'wizard_sessions';
    /**
     * The attributes that are cast.
     * 
     * @var string
     */
    protected $casts = [
        'data' => RequestDataCastable::class
    ];

    /**
     * The attributes that are fillable.
     * 
     * @var string
     */
    protected $fillable = [
        'id', 'data'
    ];

    /**
     * Construct the model.
     * 
     * @param  array  $attributes
     * @return void
     */
    public function __construct(array $attributes = [])
    {
        parent::__construct(array_merge([
            'id' => Str::random(32)
        ], array_filter($attributes)));
    }

    /**
     * Get the parent user.
     */
    public function user()
    {
        return $this->morphTo();
    }

    /**
     * Get the parent model.
     */
    public function model()
    {
        return $this->morphTo();
    }

    /**
     * Associate the model with the session.
     * 
     * @param  \Illuminate\Database\Eloquent\Model  $model;
     * @return $this;
     */
    public function associateModel(Model $model)
    {
        $this->model()->associate($model);

        return $this;
    }

    /**
     * Associate the user with the session.
     * 
     * @param  \Illuminate\Database\Eloquent\Model  $model;
     * @return $this;
     */
    public function associateUser(Model $model)
    {
        $this->user()->associate($model);

        return $this;
    }

    /**
     * Get the dirty attributes and exclude the id attribute.
     * 
     * @return bool
     */
    public function getDirty()
    {
        return array_diff_key(parent::getDirty(), array_flip(['id']));
    }

    /**
     * Merge the request into the data array.
     * 
     * @param  \Illuminate\Http\Request  $request
     * @return $this
     */
    public function merge(Request $request)
    {
        $this->data->merge($request);
        
        return $this;
    }

    /**
     * Restore the session into the request.
     * 
     * @param  \Illuminate\Http\Request  $request
     * @return $this
     */
    public function restore(Request $request)
    {
        if(!$request->headers->has(config('wizard.session.header'))) {
            $request->headers->set(config('wizard.session.header'), $this->id);
        }

        $this->data->restore($request);

        return $this;
    }

    /**
     * Restore the session into the request.
     * 
     * @param  \Illuminate\Http\Request  $request
     * @return $this
     */
    public function restoreIfExists(Request $request)
    {
        if($request->headers->has(config('wizard.session.header'))) {
            $this->data->restore($request);
        }

        return $this;
    }

    /**
     * Restore, merge and save the session.
     * 
     * @param  \Illuminate\Http\Request  $request
     * @return bool
     */
    public function restoreAndSave(Request $request)
    {
        return $this->restore($request)
            ->merge($request)
            ->save();
    }

    /**
     * Bind the model listeners.
     * 
     * @return void
     */
    public static function boot()
    {
        parent::boot();

        parent::deleting(function($model) {
            $model->data->files->map->delete();
        });

        parent::saving(function($model) {
            $model->data->files
                // Filter only the tmp files.
                ->filter(function($file) {
                    return $file->isTmpFile();
                })
                // Save the tmp files
                ->each(function($file) use ($model) {
                    $file->store($model->id, config('wizard.disk'));
                });

            if(!$model->user) {                
                $model->user()->associate(auth()->user());
            }
        });
    }

    /**
     * Get the session id from the request headers.
     * 
     * @param  \Illuminate\Http\Request  $request
     * @return self
     */
    public static function id(Request $request = null): ?string
    {
        $request = $request ?: request();

        return $request->header(
            $key = config('wizard.session.header'), $request->input($key)
        );
    }
    
    /**
     * Create or find the matching instances for the request.
     * 
     * @param  \Illuminate\Http\Request  $request
     * @return self
     */
    public static function request(Request $request = null): self
    {
        $request = $request ?: request();

        return static::firstOrNew([
            'id' => static::id($request)
        ]);
    }

}