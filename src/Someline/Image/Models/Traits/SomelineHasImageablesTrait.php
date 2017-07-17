<?php

namespace Someline\Image\Models\Traits;

use Illuminate\Database\Eloquent\Relations\MorphToMany;
use Someline\Models\Image\SomelineImage;

trait SomelineHasImageablesTrait
{

    /**
     * @return mixed|MorphToMany
     */
    public function images()
    {
        return $this->morphToMany(SomelineImage::class, 'imageable', 'someline_imageables', null, 'someline_image_id')
            ->withPivot('sequence', 'is_main', 'type', 'data')
            ->orderBy('sequence', 'asc');
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsToMany
     */
    public function main_images()
    {
        return $this->images()->wherePivot('is_main', '=', 1);
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsToMany
     */
    public function type_images($type)
    {
        return $this->images()->wherePivot('type', '=', $type);
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsToMany
     */
    public function type_main_images($type)
    {
        return $this->main_images()->wherePivot('type', '=', $type);
    }

    /**
     * @param $someline_image_ids
     * @param array $data
     */
    public function syncImages($someline_image_ids, $data = [])
    {
        $this->images()->detach();
        foreach ($someline_image_ids as $sequence => $someline_image_id) {
            $this->images()->attach($someline_image_id, array_merge([
                'sequence' => $sequence,
            ], $data));
        }
    }

    /**
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function getImages()
    {
        return $this->images;
    }

    /**
     * @return SomelineImage|null
     */
    public function getMainImage()
    {
        return $this->main_images()->first();
    }

    /**
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function getMainImages()
    {
        return $this->main_images()->get();
    }

    /**
     * @param $type
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function getTypeImages($type)
    {
        return $this->type_images($type)->get();
    }

    /**
     * @param $type
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function getTypeMainImages($type)
    {
        return $this->type_main_images($type)->get();
    }

    /**
     * @param SomelineImage $somelineImage
     * @return int
     */
    public function setAsMainImage(SomelineImage $somelineImage)
    {
        $this->images()->rawUpdate(['is_main' => false]);
        return $this->images()->updateExistingPivot($somelineImage->getKey(), ['is_main' => true]);
    }

    /**
     * @param string $type
     * @param SomelineImage $somelineImage
     * @return int
     */
    public function setAsTypeMainImage($type, SomelineImage $somelineImage)
    {
        $this->type_images($type)->rawUpdate(['is_main' => false]);
        return $this->type_images($type)->updateExistingPivot($somelineImage->getKey(), ['is_main' => true]);
    }

    /**
     * @param bool $withSomelineImageId
     * @return \Illuminate\Support\Collection
     */
    public function getImageUrls($withSomelineImageId = false)
    {
        return $this->getNamedImageUrls('image_url', $withSomelineImageId);
    }

    /**
     * @param $imageUrlAttributeName
     * @param bool $withSomelineImageId
     * @return \Illuminate\Support\Collection
     */
    public function getNamedImageUrls($imageUrlAttributeName, $withSomelineImageId = false)
    {
        $key = $withSomelineImageId ? 'someline_image_id' : null;
        return $this->getImages()->pluck($imageUrlAttributeName, $key);
    }

    /**
     * @param $type
     * @param $imageUrlAttributeName
     * @param bool $withSomelineImageId
     * @return \Illuminate\Support\Collection
     */
    public function getTypeNamedImageUrls($type, $imageUrlAttributeName, $withSomelineImageId = false)
    {
        $key = $withSomelineImageId ? 'someline_image_id' : null;
        return $this->getTypeImages($type)->pluck($imageUrlAttributeName, $key);
    }

}