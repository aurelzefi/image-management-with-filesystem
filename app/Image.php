<?php

namespace App;

use Carbon\Carbon;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Database\Eloquent\ModelNotFoundException;

class Image
{
    /**
     * Get the filesystem implementation.
     *
     * @return  \Illuminate\Filesystem\Filesystem
     */
    public function filesystem()
    {
        return new Filesystem;
    }

    public function all()
    {
        return array_map(function ($directory) {
            $path = explode(DIRECTORY_SEPARATOR, $directory);
  
            return $this->findOrFail(end($path));
        },  $this->filesystem()->directories(storage_path('app')));
    }

    public function findOrFail($id)
    {
        if (! $this->filesystem()->exists(storage_path('app/'.$id.'/meta.json'))) {
            throw (new ModelNotFoundException)->setModel(Image::class);
        }

        $meta = json_decode($this->filesystem()->get(
            storage_path('app/'.$id.'/meta.json')
        ));

        $image  = new Image;

        $image->id = $meta->id;
        $image->original_name = $meta->original_name;
        $image->extension = $meta->extension;
        $image->created_at = $meta->created_at;
        $image->updated_at = $meta->updated_at;
        $image->url = url('images/'.$id);

        return $image;
    }

    public function create(array $attributes)
    {
        $data = json_encode(array_merge($attributes, [
            'created_at' => Carbon::now()->toDateTimeString(),
            'updated_at' => Carbon::now()->toDateTimeString()
        ]), JSON_PRETTY_PRINT);

        $this->filesystem()->put(
            storage_path('app/'.$attributes['id'].'/meta.json'), $data
        );

        return $this->findOrFail($attributes['id']);
    }

    public function fill(array $attributes)
    {
        foreach ($attributes as $key => $value) {
            $this->{$key} = $value;
        }

        return $this;
    }

    public function save()
    {
        $data = json_encode([
            'id' => $this->id,
            'original_name' => $this->original_name,
            'extension' => $this->extension,
            'created_at' => $this->created_at,
            'updated_at' => Carbon::now()->toDateTimeString()
        ], JSON_PRETTY_PRINT);

        $this->filesystem()->put(
            storage_path('app/'.$this->id.'/meta.json'), $data
        );
        
        return $this->findOrFail($this->id);  
    }
}
