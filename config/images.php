<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Default Width
    |--------------------------------------------------------------------------
    | Uploaded images are scaled down to this width (in pixels) before storage.
    */
    'default_width' => 1200,

    /*
    |--------------------------------------------------------------------------
    | Default Quality
    |--------------------------------------------------------------------------
    | Starting WebP encoding quality (0-100). Lowered in steps until the
    | encoded image fits within `default_size`.
    */
    'default_quality' => 80,

    /*
    |--------------------------------------------------------------------------
    | Default Size
    |--------------------------------------------------------------------------
    | Maximum encoded file size in bytes. Quality is reduced until the
    | image fits, or the minimum quality floor is reached.
    */
    'default_size' => 300 * 1024,

];
