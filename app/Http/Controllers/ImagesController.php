<?php

namespace App\Http\Controllers;

use App\Image;
use Illuminate\Http\Request;
use Intervention\Image\ImageManager;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Auth\Access\AuthorizationException;

class ImagesController extends Controller
{
    /**
     * The image implementation.
     * 
     * @var \App\Image
     */
    protected $images;

    /**
     * The request implementation.
     * 
     * @var \Illuminate\Http\Request
     */
    protected $request;

    /**
     * The image manager implementation.
     *
     * @var \Intervention\Image\ImageManager
     */
    protected $imageManager;

    /**
     * The file system implementation.
     * 
     * @var \Illuminate\Filesystem\Filesystem
     */
    protected $filesystem;

    /**
     * The valid content headers to be used in content negotiation scenarios.
     *
     * @var array
     */
    protected $validHeaders = ['image/gif', 'image/jpeg', 'image/png'];

    /**
     * Create a new controller instance.
     *
     * @param  \App\Image  $images
     * @param  \Illuminate\Http\Request  $request
     * @param  \Intervention\Image\ImageManager  $imageManager
     * @param  \Illuminate\Filesystem\Filesystem  $filesystem
     * @return void
     */
    public function __construct(Image $images, 
                                Request $request, 
                                ImageManager $imageManager, 
                                Filesystem $filesystem)
    {
        $this->images = $images;
        $this->request = $request;
        $this->imageManager = $imageManager;
        $this->filesystem = $filesystem;
    }

	/**
     * Display a listing of the images. 
     * 
     * @return \Illuminate\Http\JsonResponse
     */
    public function index()
    {
        return response()->json($this->images->all());
    }

    /**
     * Display the content of the specified image.
     *
     * @param  int  $id
     * @return \Intervention\Image\Image
     */
    public function show($id)
    {
        $image = $this->images->findOrFail($id);

        $imageManager = $this->imageManager->make(
            storage_path('app/'.$image->id.'/image.'.$image->extension)
        );

        $this->validate($this->request, [
            'width' => 'integer|min:0',
            'height' => 'integer|min:0',
            'x_coordinate' => 'integer|min:0',
            'y_coordinate' => 'integer|min:0',
            'greyscale' => 'boolean',
            'transparency' => 'integer|min:0|max:100',
            'brightness' => 'integer|min:-100|max:100',
            'angle' => 'numeric|min:-360|max:360',
        ]);

        // Crop the image and get the cropped piece
        if ($this->request->has(['width', 'height', 'x_coordinate', 'y_coordinate'])) {
            return $imageManager->crop(
                    $this->request->input('width'), 
                    $this->request->input('height'),
                    $this->request->input('x_coordinate'),
                    $this->request->input('y_coordinate')
                )
                ->response();
        }

        // Get the resized version of the image
        if ($this->request->has(['width', 'height'])) {
            return $imageManager->resize(
                    $this->request->input('width'), 
                    $this->request->input('height')
                )
                ->response();  
        }

        // Get the greyscale version of the image
        if ($this->request->has('greyscale')) {
            return $imageManager->greyscale()->response();
        }

        // Set the opacity in the image and get the transparent version
        if ($this->request->has('transparency')) {
            return $imageManager->opacity($this->request->input('transparency'))->response();
        }

        // Get the image with the specified brightness
        if ($this->request->has(['brightness'])) {
            return $imageManager->brightness($this->request->input('brightness'))->response();
        }

        // Rotate the image and get the rotated version 
        if ($this->request->has('angle')) {
            return $imageManager->rotate($this->request->input('angle'))->response();
        }

        // Get the image in the format specified in the "Accept" header
        if (in_array($this->request->header('Accept'), $this->validHeaders())) {
            return $imageManager->encode($this->getFormat())->response();
        }

        return $imageManager->response();        
    }

    /**
     * Get the required image format from the header.
     *
     * @return string
     */
    protected function getFormat()
    {
        $headerPieces = explode('/', $this->request->header('Accept'));

        return $headerPieces[1];
    }

    /**
     * Get the valid headers.
     *
     * @return array
     */
    protected function validHeaders()
    {
        return $this->validHeaders;
    }

    /**
     * Display the JSON representation of the specified image.
     *
     * @param  int  $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function showRepresentation($id)
    {
        $image = $this->images->findOrFail($id);

        return response()->json($image);
    }

    /**
     * Download the specified image.
     *
     * @param  int  $id
     * @return \Symfony\Component\HttpFoundation\BinaryFileResponse
     */
    public function download($id)
    {   
        $image = $this->images->findOrFail($id);

        return response()->download(
            storage_path('app/'.$image->id.'/image.'.$image->extension), $image->original_name
        );
    }

    /**
     * Store a newly created image in storage.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function store()
    {
        $this->validate($this->request, [
            'file' => 'required|image'
        ]);

        $id = uniqid();
        $originalName = $this->request->file('file')->getClientOriginalName();
        $extension = $this->request->file('file')->guessExtension();

        $this->request->file('file')->move(
            storage_path('app/'.$id), 'image.'.$this->request->file('file')->guessExtension()
        );

        $image = $this->images->create([
            'id' => $id,
            'original_name' => $originalName,
            'extension' => $extension
        ]);

        return response()->json($image, 201);
    }

    /**
     * Update the specified image in storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function update($id)
    {
        $image = $this->images->findOrFail($id);

        $this->validate($this->request, [
            'file' => 'required|image'
        ]);

        $originalName = $this->request->file('file')->getClientOriginalName();
        $extension = $this->request->file('file')->guessExtension();

        $this->filesystem->delete(storage_path('app/'.$image->id.'/image.'.$image->extension));

        $this->request->file('file')->move(
            storage_path('app/'.$image->id), 'image.'.$this->request->file('file')->guessExtension()
        );

        $image->fill([
            'original_name' => $originalName,
            'extension' => $extension
        ])->save();

        return response()->json($image);
    }

    /**
     * Resize the specified image in storage.
     *
     * @param  int  $id
     * @return \Intervention\Image\Image
     */
    public function resize($id)
    {
        $image = $this->images->findOrFail($id);

        $this->validate($this->request, [
            'width' => 'required|integer|min:0',
            'height' => 'required|integer|min:0'
        ]);

        return $this->imageManager->make(storage_path('app/'.$image->id.'/image.'.$image->extension))
            ->resize($this->request->input('width'), $this->request->input('height'))
            ->save()
            ->response();
    }

    /**
     * Insert an image (ex. watermark) into the specified image in storage.
     *
     * @param  int  $id
     * @return \Intervention\Image\Image
     */
    public function insert($id)
    {
        $image = $this->images->findOrFail($id);

        $this->validate($this->request, [
            'file' => 'required|image',
            'position' => 'required|in:top-left,top,top-right,left,center,right,bottom-left,bottom,bottom-right'
        ]);

        return $this->imageManager->make(storage_path('app/'.$image->id.'/image.'.$image->extension))
            ->insert($this->request->file('file'), $this->request->input('position'))
            ->save()
            ->response();
    }

    /**
     * Crop the specified image in storage.
     *
     * @param  int  $id
     * @return \Intervention\Image\Image
     */
    public function crop($id)
    {
        $image = $this->images->findOrFail($id);

        $this->validate($this->request, [
            'width' => 'required|integer|min:0',
            'height' => 'required|integer|min:0',
            'x_coordinate' => 'required|integer|min:0',
            'y_coordinate' => 'required|integer|min:0'
        ]);

        return $this->imageManager->make(storage_path('app/'.$image->id.'/image.'.$image->extension))
            ->crop(
                $this->request->input('width'), 
                $this->request->input('height'),
                $this->request->input('x_coordinate'),
                $this->request->input('y_coordinate')
            )
            ->save()
            ->response();
    }

    /**
     * Turn into grayscale the specified image in storage.
     *
     * @param  int  $id
     * @return \Intervention\Image\Image
     */
    public function turnGreyscale($id)
    {
        $image = $this->images->findOrFail($id);

        return $this->imageManager->make(storage_path('app/'.$image->id.'/image.'.$image->extension))
            ->greyscale()
            ->save()
            ->response();
    }

    /**
     * Change the opacity of the specified image in storage.
     *
     * @param  int  $id
     * @return \Intervention\Image\Image
     */
    public function setOpacity($id)
    {
        $image = $this->images->findOrFail($id);

        $this->validate($this->request, [
            'transparency' => 'required|integer|min:0|max:100'
        ]);

        return $this->imageManager->make(storage_path('app/'.$image->id.'/image.'.$image->extension))
            ->opacity($this->request->input('transparency'))
            ->save()
            ->response();
    }

    /**
     * Change the brightness of the specified image in storage.
     *
     * @param  int  $id
     * @return \Intervention\Image\Image
     */
    public function changeBrightness($id)
    {
        $image = $this->images->findOrFail($id);

        $this->validate($this->request, [
            'brightness' => 'required|integer|min:-100|max:100'
        ]);

        return $this->imageManager->make(storage_path('app/'.$image->id.'/image.'.$image->extension))
            ->brightness($this->request->input('brightness'))
            ->save()
            ->response();
    }

    /**
     * Rotate the specified image in storage.
     *
     * @param  int  $id
     * @return \Intervention\Image\Image
     */
    public function rotate($id)
    {
        $image = $this->images->findOrFail($id);

        $this->validate($this->request, [
            'angle' => 'required|min:-360|max:360'
        ]);

        return $this->imageManager->make(storage_path('app/'.$image->id.'/image.'.$image->extension))
            ->rotate($this->request->input('angle'))
            ->save()
            ->response();
    }

    /**
     * Encode in the specified format the specified image in storage.
     *
     * @param  int  $id
     * @return \Intervention\Image\Image
     */
    public function encode($id)
    {
        $image = $this->images->findOrFail($id);

        $this->validate($this->request, [
            'format' => 'required|in:jpg,png,gif'
        ]);

        $imageManager = $this->imageManager->make(storage_path('app/'.$image->id.'/image.'.$image->extension))
            ->encode($this->request->input('format'));

        $this->filesystem->delete(storage_path('app/'.$image->id.'/image.'.$image->extension));

        $imageManager->save(
            storage_path('app/'.$image->id.'/image.'.$this->getExtension($imageManager))
        );

        $image->fill([
            'extension' => $this->getExtension($imageManager),
            'original_name' => $this->buildName($image, $imageManager)
        ])->save();

        return $imageManager->response();
    }

    /**
     * Build a new name for an image.
     *
     * @param  \App\Image  $image
     * @param  \Intervention\Image\Image  $imageManager
     * @return string
     */
    protected function buildName($image, $imageManager)
    {
        $originalName = explode('.', $image->original_name);

        return $originalName[0].'.'.$this->getExtension($imageManager);
    }

    /**
     * Build a new name for an image.
     *
     * @param  \App\Image  $image
     * @param  \Intervention\Image\Image  $imageManager
     * @return string
     */
    protected function getExtension($imageManager)
    {
        $mime = explode('/', $imageManager->mime());

        return $mime[1];
    }

    /**
     * Remove the specified image from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function destroy($id)
    {
        $image = $this->images->findOrFail($id);
        
        if ($this->filesystem->deleteDirectory(storage_path('app/'.$image->id))) {
            return response()->json([], 204);
        }

        return response()->json(['message' => 'File could not be deleted.'], 500);
    }    
}
