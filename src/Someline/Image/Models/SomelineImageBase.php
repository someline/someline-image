<?php

namespace Someline\Image\Models;


use Prettus\Repository\Contracts\Presentable;
use Prettus\Repository\Contracts\Transformable;
use Prettus\Repository\Traits\PresentableTrait;
use Someline\Base\Models\BaseModel;
use Someline\Image\Models\Traits\SomelinePivotTrait;

class SomelineImageBase extends BaseModel implements Transformable, Presentable
{
    use SomelinePivotTrait;
    use PresentableTrait;

    const MORPH_NAME = 'SomelineImage';

    protected $table = 'someline_images';

    protected $primaryKey = 'someline_image_id';

    protected $fillable = [
        'user_id', 'image_name', 'exif', 'is_gif', 'file_size', 'width', 'height'
    ];

    protected $hidden = [
        'created_at', 'created_by', 'created_ip',
        'updated_at', 'updated_by', 'updated_ip',
    ];

    /**
     * @return Int
     */
    public function getSomelineImageId()
    {
        return $this->someline_image_id;
    }

    /**
     * @return string
     */
    public function getImageName()
    {
        return $this->image_name;
    }

    /**
     * @return bool
     */
    public function isValid()
    {
        return $this->file_size > 0;
    }

    /**
     * @return null|string
     */
    public function getImageUrl()
    {
        return $this->getTypeImageUrl();
    }

    /**
     * @return null|string
     */
    public function getImageUrlAttribute()
    {
        return $this->getImageUrl();
    }

    /**
     * @param $type
     * @return null|string
     */
    public function getTypeImageUrl($type = null)
    {
        $type = $type ? $type . '/' : null;

        if ($this->isValid()) {
            return url('/image/' . $type . $this->getImageName());
        } else {
            return $this->getDefaultImageUrl($type = null);
        }
    }

    /**
     * @param null $type
     * @return null|string
     */
    public function getDefaultImageUrl($type = null)
    {
        return null;
    }

    /**
     * @return string
     */
    public function getImagePath()
    {
        $storage_path = config("someline-image.storage_path");
        return $storage_path . $this->getImageName();
    }

    /**
     * @return array
     */
    public function transform()
    {
        return [
            'someline_image_id' => $this->getSomelineImageId(),
            'image_name' => $this->getImageName(),
        ];
    }

    /**
     * @return array
     */
    public function toSimpleArray()
    {
        $somelineImage = $this;
        return [
            'someline_image_id' => $somelineImage->getSomelineImageId(),
            'someline_image_url' => $somelineImage->getImageUrl(),
            'thumbnail_image_url' => $somelineImage->getTypeImageUrl('thumbnail'),
        ];
    }

}
