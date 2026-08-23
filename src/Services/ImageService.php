<?php

namespace VanDmade\Cuztomisable\Services;

use Illuminate\Http\UploadedFile;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Intervention\Image\Drivers\Gd\Driver;
use Intervention\Image\ImageManager;
use Intervention\Image\Interfaces\ImageInterface;
use Symfony\Component\HttpFoundation\StreamedResponse;
use VanDmade\Cuztomisable\Models\Image;
use VanDmade\Cuztomisable\Services\Logs\ErrorLogService;
use Exception;
use Throwable;

class ImageService
{

    protected string $disk;
    protected int $defaultWidth;
    protected int $defaultQuality;
    protected int $defaultSize;

    public function __construct(
        protected readonly ErrorLogService $errorLogService
    ) {
        $this->disk = config('filesystems.default', 'public');
        $this->defaultWidth = config('cuztomisable.images.default_width', 1200);
        $this->defaultQuality = config('cuztomisable.images.default_quality', 80);
        $this->defaultSize = config('cuztomisable.images.default_size', 300 * 1024);
    }

    public function find(int $id, bool $includeTrashed = false): ?Image
    {
        $query = Image::query();
        if ($includeTrashed) {
            $query->withTrashed();
        }
        return $query->find($id);
    }

    public function view(int $id, bool $url = false, ?int $temporaryLength = null): StreamedResponse|string
    {
        $image = $this->find($id);
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
        $image->blendTransparency();
        try {
            $encoded = $this->encodeWithinTargetSize($image);
        } catch (Throwable $error) {
            $this->errorLogService->log($error, 'WebP encoding failed during upload', [
                'original_name' => $file->getClientOriginalName(),
                'original_mime' => $file->getMimeType(),
                'width' => $image->width(),
                'height' => $image->height(),
                'memory_usage' => memory_get_usage(true),
                'memory_peak' => memory_get_peak_usage(true),
            ]);
            throw $error;
        }
        $randomString = Str::random(40);
        $filename = $uploadPath.'/'.$randomString.'.webp';
        try {
            $disk->put($filename, (string) $encoded);
        } catch (Throwable $error) {
            $this->errorLogService->log($error, 'Image upload to storage disk failed', [
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
        $image = $this->find($id);
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
        int $borderWidth = 6
    ): string {
        ini_set('memory_limit', '512M');
        $manager = new ImageManager(new Driver());
        $count = count($images);
        if ($count === 0) {
            throw new Exception('No images provided.', 422);
        }
        $loaded = [];
        foreach ($images as $item) {
            if ($item instanceof UploadedFile) {
                $img = $manager->read($item->getRealPath());
            } elseif (is_string($item)) {
                $img = $manager->read($item);
            } else {
                continue;
            }
            $img->scaleDown(width: $this->defaultWidth);
            $loaded[] = $img;
        }
        if (empty($loaded)) {
            throw new Exception('No images provided.', 422);
        }
        $hasBorder = $borderColor !== null && count($loaded) > 1;
        $gaps = $hasBorder ? (count($loaded) - 1) * $borderWidth : 0;
        // WebP can't encode canvases taller than 16383px ("gd-webp encoding
        // failed"); shrink proportionally so the stacked height fits.
        $maxCanvasDimension = 16383;
        $sumHeights = array_sum(array_map(fn($img) => $img->height(), $loaded));
        if ($sumHeights + $gaps > $maxCanvasDimension) {
            $scale = ($maxCanvasDimension - $gaps) / $sumHeights;
            foreach ($loaded as $img) {
                $img->scale(width: (int) floor($img->width() * $scale));
            }
        }
        $maxWidth = (int) max(array_map(fn($img) => $img->width(), $loaded));
        $totalHeight = (int) array_sum(array_map(fn($img) => $img->height(), $loaded)) + $gaps;
        $canvas = $manager->create($maxWidth, $totalHeight);
        $y = 0;
        foreach ($loaded as $i => $img) {
            $canvas->place($img, 'top-left', 0, $y);
            $y += $img->height();
            if ($hasBorder && $i < count($loaded) - 1) {
                $canvas->drawRectangle(0, $y, function ($draw) use ($borderColor, $borderWidth, $maxWidth) {
                    $draw->size($maxWidth, $borderWidth);
                    $draw->background($borderColor);
                });
                $y += $borderWidth;
            }
        }
        // The freshly created canvas carries a transparent alpha channel that
        // GD's WebP encoder fails to write ("gd-webp encoding failed"); flatten it.
        $canvas->blendTransparency();
        try {
            return (string) $canvas->toWebp(50);
        } catch (Throwable $error) {
            $this->errorLogService->log($error, 'Combine WebP encoding failed', [
                'image_count' => count($loaded),
                'sources' => array_map(fn($img) => $img->width().'x'.$img->height(), $loaded),
                'canvas_width' => $canvas->width(),
                'canvas_height' => $canvas->height(),
                'memory_usage' => memory_get_usage(true),
                'memory_peak' => memory_get_peak_usage(true),
            ]);
            throw $error;
        }
    }

    public function resize(int $id, int $width): void
    {
        $image = $this->find($id);
        if (empty($image)) {
            throw new Exception('The image was not found.', 404);
        }
        $loaded = $this->readFromDisk($image);
        // Only ever shrinks - upscaling would just soften the image, not add real detail
        if ($width >= $loaded->width()) {
            throw new Exception('The requested width must be smaller than the current image width.', 422);
        }
        $loaded->scale(width: $width);
        $loaded->blendTransparency();
        $this->writeToDisk($image, (string) $loaded->toWebp($this->defaultQuality), $loaded->width(), $loaded->height());
    }

    public function crop(
        int $id,
        int $width,
        int $height,
        int $x = 0,
        int $y = 0
    ): void {
        $image = $this->find($id);
        if (empty($image)) {
            throw new Exception('The image was not found.', 404);
        }
        $loaded = $this->readFromDisk($image);
        $loaded->crop($width, $height, $x, $y);
        $loaded->blendTransparency();
        $this->writeToDisk($image, (string) $loaded->toWebp($this->defaultQuality), $loaded->width(), $loaded->height());
    }

    public function optimize(int $id): void
    {
        $image = $this->find($id);
        if (empty($image)) {
            throw new Exception('The image was not found.', 404);
        }
        $loaded = $this->readFromDisk($image);
        $loaded->scaleDown(width: $this->defaultWidth);
        $loaded->blendTransparency();
        try {
            $encoded = $this->encodeWithinTargetSize($loaded);
        } catch (Throwable $error) {
            $this->errorLogService->log($error, 'WebP encoding failed during optimize', [
                'image_id' => $image->id,
                'width' => $loaded->width(),
                'height' => $loaded->height(),
                'memory_usage' => memory_get_usage(true),
                'memory_peak' => memory_get_peak_usage(true),
            ]);
            throw $error;
        }
        $this->writeToDisk($image, $encoded, $loaded->width(), $loaded->height());
    }

    private function readFromDisk(Image $image): ImageInterface
    {
        $disk = Storage::disk($image->disk);
        if (!$disk->exists($image->path)) {
            throw new Exception('The image file is missing.', 404);
        }
        return (new ImageManager(new Driver()))->read($disk->get($image->path));
    }

    private function writeToDisk(Image $image, string $encoded, int $width, int $height): void
    {
        Storage::disk($image->disk)->put($image->path, $encoded);
        $parameters = array_merge(json_decode($image->parameters ?? '{}', true) ?? [], [
            'width' => $width,
            'height' => $height,
            'size' => strlen($encoded),
        ]);
        $image->parameters = json_encode($parameters);
        $image->save();
    }

    private function encodeWithinTargetSize(mixed $image): string
    {
        $quality = $this->defaultQuality;
        $encoded = (string) $image->toWebp($quality);
        while (strlen($encoded) > $this->defaultSize && $quality > 20) {
            $quality -= 5;
            $encoded = (string) $image->toWebp($quality);
        }
        return $encoded;
    }

    public function generatePath(): string
    {
        if (!Auth::check()) {
            return 'uploads/';
        }
        return 'uploads/'.Auth::user()->id.'/'.(Auth::user()->token ?? 'token').'/';
    }

}