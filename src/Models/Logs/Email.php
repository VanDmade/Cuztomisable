<?php

namespace VanDmade\Cuztomisable\Models\Logs;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use VanDmade\Cuztomisable\Concerns\Auditable;

class Email extends Model
{

    use HasFactory, Auditable;

    protected $table = 'email_logs';

    protected $fillable = [
        'user_id',
        'to',
        'cc',
        'bcc',
        'from',
        'subject',
        'parameters',
        'created_by',
    ];

    protected $casts = [];

    protected $hidden = [];

    public function to(): Attribute
    {
        return Attribute::make(
            get: fn (string|null $value) => json_decode($value) ? json_decode($value, true) : [],
            set: fn (string|array $value) => !is_null($value) ? (is_array($value) ? json_encode($value) : $value) : null
        );
    }

    public function cc(): Attribute
    {
        return Attribute::make(
            get: fn (string|null $value) => json_decode($value) ? json_decode($value, true) : [],
            set: fn (string|array $value) => !is_null($value) ? (is_array($value) ? json_encode($value) : $value) : null
        );
    }

    public function bcc(): Attribute
    {
        return Attribute::make(
            get: fn (string|null $value) => json_decode($value) ? json_decode($value, true) : [],
            set: fn (string|array $value) => !is_null($value) ? (is_array($value) ? json_encode($value) : $value) : null
        );
    }

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
