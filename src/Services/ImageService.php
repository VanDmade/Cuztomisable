<?php

namespace VanDmade\Cuztomisable\Services;

use VanDmade\Cuztomisable\Models\Image;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Http\UploadedFile;
use Illuminate\Database\Eloquent\Model;
use Exception;

class ImageService
{

    protected string $disk = 'public';

    public function get(int $id, bool $includeTrashed = false): ?Image
    {
        $query = Image::query();
        if ($includeTrashed) {
            $query->withTrashed();
        }
        return $query->find($id);
    }

    public function view(int $id, bool $url = false, ?int $temporaryLength = null): StreamedResponse|string
    {
        $image = $this->get($id);
        if (empty($image)) {
            throw new Exception('The image was not found.', 404);
        }
        $disk = Storage::disk($this->disk);
        $path = $image->path;
        // If a URL is requested
        if ($url) {
            // Generate a temporary signed URL if requested
            if ($temporaryLength && $disk->getDriver()->getAdapter()->getPathPrefix() === null) {
                return $disk->temporaryUrl($path, now()->addSeconds($temporaryLength));
            }
            return $disk->url($path);
        }
        // Otherwise return a streamed file response
        if (!$disk->exists($path)) {
            throw new Exception('The image file is missing.', 404);
        }
        $stream = $disk->readStream($path);
        if ($stream === false) {
            throw new Exception('The image file could not be streamed.', 500);
        }
        return new StreamedResponse(function() use ($stream) {
            try {
                fpassthru($stream);
            } finally {
                fclose($stream);
            }
        }, 200, [
            'Content-Type' => $disk->mimeType($path),
            'Content-Length' => $disk->size($path),
            'Content-Disposition' => 'inline; filename="'.basename($path).'"',
        ]);
    }

    public function upload(UploadedFile $file, string $uploadPath = 'uploads'): Image|false
    {
        // Checks to see if the image exists and is valid prior to uploading it to the bucket
        if (!$file->isValid()) {
            return false;
        }
        $uploadPath = trim($uploadPath, '/');
        $disk = Storage::disk($this->disk);
        $path = $file->store($uploadPath ?: 'uploads', $this->disk);
        if (!$disk->exists($path)) {
            throw new Exception('Image not stored in S3: '.$path);
        }
        // Makes it so that the uploaded image is visible and can be used within the system
        $disk->setVisibility($path, 'public');
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

    public function delete(int $id, bool $removeCompletely = false): bool
    {
        $image = $this->get($id);
        if (empty($image)) {
            throw new Exception('The image was not found.', 404);
        }
        $path = $image->path;
        if ($removeCompletely) {
            $disk = Storage::disk($this->disk);
            if ($disk->exists($path)) {
                // Removes the image from the bucket
                $disk->delete($path);
            }
            $image->removed_from_storage_at = now();
            $image->save();
            return (bool) $image->forceDelete();
        }
        $image->deleted_by = Auth::check() ? Auth::user()->id : null;
        $image->save();
        return (bool) $image->delete();
    }

    public function generic(Model $model, array $data): bool
    {
        // Sets up the generic upload/management of files
        if (isset($data['image']) && !is_null($data['image'])) {
            if (strpos($data['image']['id'], 'NEW') !== false) {
                $image = $this->upload($data['image']['file']);
                if ($image === false) {
                    return false;
                }
                $model->image_id = $image->id;
                $model->save();
            }
        } elseif (!is_null($model->image_id)) {
            $this->delete($model->image_id, true);
            // The images was deleted or never set
            $model->image_id = null;
            $model->save();
        }
        return true;
    }

    public function resize(int $id, int $width): void
    {
        $image = $this->get($id);
        if (empty($image)) {
            throw new Exception('The image was not found.', 404);
        }
        // Checks the sizes to make sure they can be used
    }

    public function crop(
        int $id,
        int $width,
        int $height,
        int $x = 0,
        int $y = 0
    ): void {
        $image = $this->get($id);
        if (empty($image)) {
            throw new Exception('The image was not found.', 404);
        }
        // Checks the sizes to make sure they can be used
    }

    public function optimize(int $id): void
    {
        $image = $this->get($id);
        if (empty($image)) {
            throw new Exception('The image was not found.', 404);
        }

    }

    public function generatePath(): string
    {
        if (!Auth::check()) {
            return 'uploads/';
        }
        return 'uploads/'.Auth::user()->id.'/'.(Auth::user()->token ?? 'token').'/';
    }

}