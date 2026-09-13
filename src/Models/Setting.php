<?php

namespace VanDmade\Cuztomisable\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * An admin-editable key/value setting.
 */
class Setting extends Model
{

    protected $table = 'settings';

    protected $fillable = [
        'key',
        'value',
    ];

}
