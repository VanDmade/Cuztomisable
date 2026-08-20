<?php

namespace VanDmade\Cuztomisable\Models\Logs;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use VanDmade\Cuztomisable\Concerns\Auditable;

class Text extends Model
{

    use HasFactory, Auditable;

    protected $table = 'text_logs';

    protected $fillable = [
        'user_id',
        'country_code',
        'number',
        'message',
        'parameters',
        'created_by',
    ];

    protected $casts = [];

    protected $hidden = [];

    public function parameters(): Attribute
    {
        return Attribute::make(
            get: fn (string|null $value) => json_decode($value) ? json_decode($value, true) : [],
            set: fn (string|array $value) => !is_null($value) ? (is_array($value) ? json_encode($value) : $value) : null
        );
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(config('auth.providers.users.model'), 'user_id');
    }

}
