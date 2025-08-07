<?php

namespace VanDmade\Cuztomisable\Services;

use VanDmade\Cuztomisable\Models\Image;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Auth;
use Exception;

class ImageService
{

    protected $disk = 'public';

    public function get($id, $includeTrashed = false)
    {
        $query = Image::query();
        if ($includeTrashed) {
            $query->withTrashed();
        }
        return $query->find($id);
    }

    public function view($id, $url = false, $temporaryLength = null)
    {
        $image = $this->get($id);
        if (empty($image)) {
            throw new Exception('The image was not found.', 404);
        }
        $path = $image->path;
        // If a URL is requested
        if ($url) {
            // Generate a temporary signed URL if requested
            if ($temporaryLength && Storage::disk($this->disk)->getDriver()->getAdapter()->getPathPrefix() === null) {
                return Storage::disk($this->disk)->temporaryUrl($path, now()->addSeconds($temporaryLength));
            }
            return Storage::disk($this->disk)->url($path);
        }
        // Otherwise return a streamed file response
        if (!Storage::disk($this->disk)->exists($path)) {
            throw new Exception('The image file is missing.', 404);
        }
        $stream = Storage::disk($this->disk)->readStream($path);
        return new StreamedResponse(function() use ($stream) {
            fpassthru($stream);
        }, 200, [
            'Content-Type' => Storage::disk($this->disk)->mimeType($path),
            'Content-Length' => Storage::disk($this->disk)->size($path),
            'Content-Disposition' => 'inline; filename="'.basename($path).'"',
        ]);
    }

    public function upload($file, $uploadPath='uploads')
    {
        // Checks to see if the image exists and is valid prior to uploading it to the bucket
        if (!$file->isValid()) {
            return false;
        }
        $uploadPath = trim($uploadPath, '/').'/';
        $path = $file->store($uploadPath, $this->disk);
        if (!Storage::disk($this->disk)->exists($path)) {
            throw new Exception('Image not stored in S3: '.$path);
        }
        // Makes it so that the uploaded image is visible and can be used within the system
        Storage::disk($this->disk)->setVisibility($path, 'public');
        [$width, $height] = getimagesize($file->getRealPath());
        return Image::create([
            'name' => $file->getClientOriginalName(),
            'extension' => $file->getClientOriginalExtension(),
            'path' => $path,
            'disk' => $this->disk,
            'parameters' => json_encode([
                'mime_type' => $file->getClientMimeType(),
                'size' => $file->getSize(),
                'width' => $width ?? 0,
                'height' => $height ?? 0,
            ]),
            'original' => true,
        ]);
    }

    public function delete($id, $removeCompletely = false)
    {
        $image = $this->get($id);
        if (empty($image)) {
            throw new Exception('The image was not found.', 404);
        }
        $image->deleted_at = date('Y-m-d H:i:s');
        $image->deleted_by = Auth::check() ? Auth::user()->id : null;
        if ($removeCompletely && Storage::disk($this->disk)->exists($path)) {
            // Removes the image from the bucket
            Storage::disk($this->disk)->delete($path);
        }
        return $image->save();
    }

    public function generic($model, array $data)
    {
        // Sets up the generic upload/management of files
        if (isset($data['image']) && !is_null($data['image'])) {
            if (strpos($data['image']['id'], 'NEW') !== false) {
                $image = $this->upload($data['image']['file']);
                $model->image_id = $image->id;
                $model->save();
            }
        } elseif (!is_null($model->image_id)) {
            $this->delete($model->image_id);
            // The images was deleted or never set
            $model->image_id = null;
            $model->save();
        }
        return true;
    }

    public function resize($id, int $width)
    {
        $image = $this->get($id);
        if (empty($image)) {
            throw new Exception('The image was not found.', 404);
        }
        // Checks the sizes to make sure they can be used
    }

    public function crop(
        $id,
        int $width,
        int $height,
        int $x = 0,
        int $y = 0
    ) {
        $image = $this->get($id);
        if (empty($image)) {
            throw new Exception('The image was not found.', 404);
        }
        // Checks the sizes to make sure they can be used
    }

    public function optimize($id)
    {
        $image = $this->get($id);
        if (empty($image)) {
            throw new Exception('The image was not found.', 404);
        }

    }

    public function generatePath()
    {
        if (!Auth::check()) {
            return 'uploads/';
        }
        return 'uploads/'.Auth::user()->id.'/'.(Auth::user()->token ?? 'token').'/';
    }

}