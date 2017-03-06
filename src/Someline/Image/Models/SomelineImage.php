<?php namespace Someline\Image\Models;


use Someline\Base\Models\BaseModel;

class SomelineImage extends BaseModel
{

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
     * @return null|string
     */
    public function getImageUrl()
    {
        if ($this->file_size > 0) {
            return url('/image/' . $this->image_name);
        } else {
            return null;
        }
    }

    public function getImagePath()
    {
        $storage_path = config("someline-image.storage_path");
        return $storage_path . $this->image_name;
    }

}
