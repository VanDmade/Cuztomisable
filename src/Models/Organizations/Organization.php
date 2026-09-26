<?php

namespace VanDmade\Cuztomisable\Models\Organizations;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use VanDmade\Cuztomisable\Concerns\CuztomisableOrganization;
use VanDmade\Cuztomisable\Concerns\SoftDeletes;

/**
 * The default concrete Organization model Cuztomisable ships.
 */
class Organization extends Model
{

    use HasFactory, SoftDeletes, CuztomisableOrganization;

    protected $table = 'organizations';

    protected $fillable = [
        'name',
        'slug',
        'deleted_at',
    ];

}
