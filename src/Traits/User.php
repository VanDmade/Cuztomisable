<?php

namespace VanDmade\Cuztomisable\Traits;

trait CuztomisableUser
{

    public function fooItems()
    {
        return $this->hasMany(Foo::class);
    }

}