<?php

namespace Someline\Image\Models\Traits;

use Someline\Models\Image\SomelineImage;

trait SomelineHasOneImageTrait
{

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasOne
     */
    public function someline_image()
    {
        return $this->hasOne(SomelineImage::class, 'someline_image_id', 'someline_image_id');
    }

    /**
     * @return SomelineImage|null
     */
    public function getSomelineImage()
    {
        return $this->someline_image;
    }

    /**
     * @return \Illuminate\Contracts\Routing\UrlGenerator|null|string
     */
    public function getSomelineImageUrl()
    {
        $somelineImageType = $this->getSomelineImageType();
        return $this->getSomelineImageUrlForType($somelineImageType);
    }

    /**
     * @param $type
     * @return \Illuminate\Contracts\Routing\UrlGenerator|null|string
     */
    public function getSomelineImageUrlForType($type)
    {
        $somelineImage = $this->getSomelineImage();
        if (!$somelineImage) {
            return $this->getSomelineImageDefaultUrl();
        }
        if (!empty($type)) {
            return $somelineImage->getTypeImageUrl($type);
        } else {
            return $somelineImage->getImageUrl();
        }
    }

    /**
     * @return \Illuminate\Contracts\Routing\UrlGenerator|null|string
     */
    public function getSomelineImageUrlAttribute()
    {
        return $this->getSomelineImageUrl();
    }

    /**
     * @return int|null
     */
    public function getSomelineImageId()
    {
        return $this->someline_image_id;
    }

    /**
     * @return null|string
     */
    public function getSomelineImageName()
    {
        $someline_image = $this->getSomelineImage();
        if ($someline_image) {
            return $someline_image->getImageName();
        }
        return null;
    }

    /**
     * @return null|string
     */
    public function getSomelineImageNameAttribute()
    {
        return $this->getSomelineImageName();
    }

    /**
     * @return null
     */
    protected function getSomelineImageType()
    {
        return null;
    }

    /**
     * @return null
     */
    protected function getSomelineImageDefaultUrl()
    {
        return null;
    }

}