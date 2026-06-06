<?php

namespace VanDmade\Cuztomisable\Models;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use VanDmade\Cuztomisable\Traits\Concerns\Auditable;
use VanDmade\Cuztomisable\Traits\Concerns\SoftDeletes;
use Exception;

class Image extends Model
{
    use HasFactory, SoftDeletes, Auditable;

    protected $table = 'images';

    protected $fillable = [
        'name',
        'extension',
        'path',
        'disk',
        'removed_from_storage_at',
        'original',
        'parameters',
        'created_from_image_id',
        'created_by',
        'deleted_at',
        'deleted_by',
    ];

    protected $casts = [
        'original' => 'boolean',
        'removed_from_storage_at' => 'datetime',
        'deleted_at' => 'datetime',
    ];

    protected $hidden = [
        'created_from_image_id',
        'created_by',
        'deleted_by',
    ];

    protected $appends = ['full_url'];


    public function output(): string
    {
        if (!is_null($this->removed_from_storage_at)) {
            throw new Exception(__('cuztomisable/user.image.errors.not_found'), 404);
        }
        return Storage::disk($this->disk)->url($this->path);
    }

    public function temporary(string $length = '+5 seconds'): string
    {
        if (!is_null($this->removed_from_storage_at)) {
            throw new Exception(__('cuztomisable/user.image.errors.not_found'), 404);
        }
        return Storage::disk($this->disk)->temporaryUrl($this->path, $length);
    }

    public function size(): Attribute
    {
        $parameters = json_decode($this->parameters, true);
        return Attribute::make(
            get: fn () => $parameters['size'] ?? null
        );
    }

    public function getFullUrlAttribute(): string
    {
        if (!is_null($this->removed_from_storage_at)) {
            throw new Exception(__('cuztomisable/user.image.errors.not_found'), 404);
        }
        if (env('APP_ENV') === 'local') {
            return 'http://192.168.10.147:8000/storage/'.trim(Storage::disk($this->disk)->url($this->path), '/');
        }
        return Storage::disk($this->disk)->url($this->path);
    }

    public function createdFromImage(): BelongsTo
    {
        return $this->belongsTo(Image::class, 'created_from_image_id');
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(config('auth.providers.users.model'), 'created_by');
    }

    public function deletedBy(): BelongsTo
    {
        return $this->belongsTo(config('auth.providers.users.model'), 'deleted_by');
    }

}
