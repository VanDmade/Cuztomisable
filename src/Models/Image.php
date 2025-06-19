<?php

namespace VanDmade\Cuztomisable\Models;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use VanDmade\Cuztomisable\Models\Users;
use Auth;
use Storage;

class Image extends Model
{
    use HasFactory, SoftDeletes;

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
        'removed_from_storage_at' => 'boolean',
        'deleted_at' => 'datetime',
    ];

    protected $hidden = [
        'created_from_image_id',
        'created_by',
        'deleted_by',
    ];

    protected $appends = ['full_url'];

    public static function boot()
    {
        parent::boot();
        self::creating(function($model) {
            $model->created_by = Auth::check() ? Auth::user()->id : null;
        });
    }

    public function output()
    {
        if (!is_null($this->removed_from_storage_at)) {
            throw new Exception(__('cuztomisable/user.image.errors.not_found'), 404);
        }
        return Storage::disk($this->disk)->url($this->path);
    }

    public function temporary($length = '+5 seconds')
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

    public function getFullUrlAttribute()
    {
        if (!is_null($this->removed_from_storage_at)) {
            throw new Exception(__('cuztomisable/user.image.errors.not_found'), 404);
        }
        return Storage::disk($this->disk)->url($this->path);
    }

    public function createdFromImage()
    {
        return $this->belongsTo(Image::class, 'created_from_image_id');
    }

    public function createdBy()
    {
        return $this->belongsTo(Users\User::class, 'created_by');
    }

    public function deletedBy()
    {
        return $this->belongsTo(Users\User::class, 'deleted_by');
    }

}
