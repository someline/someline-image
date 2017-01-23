<?php

namespace Someline\Component\Category\Models\Traits;

use Someline\Image\Models\SomelineImage;

trait SomelineImageableTrait
{

    public function images()
    {
        return $this->morphToMany(SomelineImage::class, 'imageable', 'someline_imageables');
    }

}