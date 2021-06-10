<?php

namespace Actengage\Wizard;

use Illuminate\Contracts\Support\Arrayable;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Storage;
use JsonSerializable;

class RequestData implements Arrayable, JsonSerializable {

    public $files;
    
    public $input;
    
    public $session;
    
    /**
     * Construct the RequestData.
     * 
     * @param  \Actengage\Wizard\Session  $session
     * @param  array|null  $files
     * @param  array|null  $input
     */
    public function __construct(Session $session, ?array $files, ?array $input)
    {
        $this->session = $session;

        $this->files = collect($files ?? [])->map(function($file) {
            return is_array($file) ? new UploadedFile(
                $this->absolutePath($file['disk_path']), $file['original_name'], $file['mime_type']
            ) : $file;
        });

        $this->input = collect($input);
    }

    /**
     * Return the absolute path on the disk.
     * 
     * @param  string  $relativePath
     * @return string
     */
    public function absolutePath(string $relativePath)
    {
        return Storage::disk(config('wizard.disk'))->path($relativePath);
    }

    /**
     * Remove the items from a collection that already exist in the request.
     * 
     * @param  \Illuminate\Support\Collection  $collection
     * @param  \Illuminate\Http\Request  $request
     * @return  \Illuminate\Support\Collection
     */
    protected function filterForRequest(Collection $collection, Request $request): Collection
    {
        return $collection->filter(function($value, $key) use ($request) {
            return !$request->has($key);
        });
    }

    /**
     * Cast to json.
     * 
     * @return array
     */
    public function jsonSerialize()
    {
        return [
            'input' => $this->input->toArray(),
            'files' => $this->files->toArray(),
        ];
    }

    /**
     * Cast to an array.
     * 
     * @return array
     */
    public function toArray()
    {
        return $this->jsonSerialize();
    }

    /**
     * Merge the request into the data array.
     * 
     * @param  \Illuminate\Http\Request  $request
     * @return $this
     */
    public function merge(Request $request)
    {
        // This is necessary to merge the files properly.
        $request = Request::createFromBase($request);

        $this->files = $this->files->merge(
            collect($request->allFiles())->map(function($file) {
                return UploadedFile::createFromBase($file);
            })
        );

        $this->input = $this->input->merge($request->input());

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
        // Merge the missing input back into the request.
        $request->merge(
            $this->filterForRequest($this->input, $request)->all()
        );

        // Merge the missing files back into the request.
        $this->filterForRequest($this->files, $request)
            ->each(function($value, $key) use ($request) {
                $request->files->set($key, $value);
            });
        
        return $this;
    }


}