<?php

namespace VanDmade\Cuztomisable\Services;

use VanDmade\Cuztomisable\Models\Image;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Http\UploadedFile;
use Illuminate\Database\Eloquent\Model;
use Intervention\Image\ImageManager;
use Intervention\Image\Drivers\Gd\Driver;
use Illuminate\Support\Str;
use Exception;
use Log;
use Throwable;

class ImageService
{

    protected string $disk;
    protected int $defaultWidth;
    protected int $defaultQuality;
    protected int $defaultSize;

    public function __construct()
    {
        $this->disk = config('filesystems.default', 'public');
        $this->defaultWidth = config('cuztomisable.global.images.default_width', 1200);
        $this->defaultQuality = config('cuztomisable.global.images.default_quality', 80);
        $this->defaultSize = config('cuztomisable.global.images.default_size', 300 * 1024);
    }

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
            if ($temporaryLength) {
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

    public function upload(UploadedFile $file, string $uploadPath = 'uploads', ?string $diskName = null, array $extra = []): Image|false
    {
        ini_set('memory_limit', '512M');
        if (!$file->isValid()) {
            return false;
        }
        $uploadPath = trim($uploadPath, '/');
        $diskName = $diskName ?? $this->disk;
        $disk = Storage::disk($diskName);
        $manager = new ImageManager(new Driver());
        $image = $manager->read($file->getRealPath());
        $image->scaleDown(width: $this->defaultWidth);
        $quality = $this->defaultQuality;
        $encoded = $image->toWebp($quality);
        while (strlen((string) $encoded) > $this->defaultSize && $quality > 20) {
            $quality -= 5;
            $encoded = $image->toWebp($quality);
        }
        $randomString = Str::random(40);
        $filename = $uploadPath.'/'.$randomString.'.webp';
        try {
            $disk->put($filename, (string) $encoded);
        } catch (Throwable $error) {
            Log::error('S3 upload failed', [
                'message' => $error->getMessage(),
                'previous' => $error->getPrevious()?->getMessage(),
            ]);
            throw $error;
        }
        $path = $filename;
        [$width, $height] = [$image->width(), $image->height()];
        return Image::create([
            'name' => $file->getClientOriginalName(),
            'extension' => 'webp',
            'path' => $path,
            'disk' => $diskName,
            'parameters' => json_encode(array_merge([
                'mime_type' => 'image/webp',
                'size' => strlen((string) $encoded),
                'width' => $width ?? 0,
                'height' => $height ?? 0,
            ], $extra)),
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

    public function storeEncoded(
        string $contents,
        string $name,
        string $uploadPath = 'uploads',
        ?string $diskName = null,
        array $extra = []
    ): Image {
        $uploadPath = trim($uploadPath, '/');
        $diskName = $diskName ?? $this->disk;
        $disk = Storage::disk($diskName);
        $filename = $uploadPath.'/'.Str::random(40).'.webp';
        $disk->put($filename, $contents);
        $img = (new ImageManager(new Driver()))->read($contents);
        return Image::create([
            'name' => $name,
            'extension' => 'webp',
            'path' => $filename,
            'disk' => $diskName,
            'parameters' => json_encode(array_merge([
                'mime_type' => 'image/webp',
                'size' => strlen($contents),
                'width' => $img->width(),
                'height' => $img->height(),
            ], $extra)),
            'original' => true,
        ]);
    }

    public function combine(
        array $images,
        ?string $borderColor = null,
        int $borderWidth = 4
    ): string {
        ini_set('memory_limit', '512M');
        $manager = new ImageManager(new Driver());
        $count = count($images);
        if ($count === 0) {
            throw new Exception('No images provided.', 422);
        }
        $sliceWidth = (int) ($this->defaultWidth / $count);
        $loaded = [];
        foreach ($images as $item) {
            if ($item instanceof UploadedFile) {
                $img = $manager->read($item->getRealPath());
            } elseif (is_string($item)) {
                $img = $manager->read($item);
            } else {
                continue;
            }
            $img->scaleDown(width: $sliceWidth);
            $loaded[] = $img;
        }
        if (empty($loaded)) {
            throw new Exception('No images provided.', 422);
        }
        $hasBorder = $borderColor !== null && count($loaded) > 1;
        $gaps = $hasBorder ? (count($loaded) - 1) * $borderWidth : 0;
        $totalWidth = (int) array_sum(array_map(fn($img) => $img->width(), $loaded)) + $gaps;
        $maxHeight  = (int) max(array_map(fn($img) => $img->height(), $loaded));
        $canvas = $manager->create($totalWidth, $maxHeight);
        $x = 0;
        foreach ($loaded as $i => $img) {
            $canvas->place($img, 'top-left', $x, 0);
            $x += $img->width();
            if ($hasBorder && $i < count($loaded) - 1) {
                $canvas->drawRectangle($x, 0, function ($draw) use ($borderColor, $borderWidth, $maxHeight) {
                    $draw->size($borderWidth, $maxHeight);
                    $draw->background($borderColor);
                });
                $x += $borderWidth;
            }
        }
        return (string) $canvas->toWebp(80);
    }

    public function resize(int $id, int $width): void
    {
        $image = $this->get($id);
        if (empty($image)) {
            throw new Exception('The image was not found.', 404);
        }
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