<?php

namespace Actengage\Wizard;

use Illuminate\Http\UploadedFile as BaseUploadedFile;
use Illuminate\Support\Str;
use JsonSerializable;

class UploadedFile extends BaseUploadedFile implements JsonSerializable {
    
    /**
     * The stored relative path.
     * 
     * @var string
     */
    private $diskPath;

    /**
     * Get the relative path on the disk.
     */
    public function diskPath()
    {
        return $this->diskPath ?? (
            str_replace(app('wizard.filesystem')->path(null), '', $this->getPathname())
        );
    }

    /**
     * Determine if the file is in the /tmp/files directory.
     * 
     * @return bool
     */
    public function isTmpFile()
    {
        return Str::contains($this->getPath(), rtrim(sys_get_temp_dir(), '/'));
    }

    /**
     * Serialize the file.
     * 
     * @return array
     */
    public function jsonSerialize()
    {
        return [
            'disk_path' => $this->diskPath(),
            'original_name' => $this->getClientOriginalName(),
            'mime_type' => $this->getMimeType(),
            'error' => $this->getError()    
        ];
    }

    /**
     * Store the uploaded file on a filesystem disk.
     *
     * @param  string  $path
     * @param  array|string  $options
     * @return $this
     */
    public function store($path, $options = [])
    {      
        $this->diskPath = parent::store($path, $options);

        return $this;
    }

    /**
     * Delete the uploaded file on a filesystem disk.
     *
     * @param  string  $path
     * @return $this
     */
    public function delete()
    {      
        app('wizard.filesystem')->delete($this->diskPath());

        $this->diskPath = null;

        return $this;
    }

}