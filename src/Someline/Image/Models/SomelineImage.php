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

    public function getSomelineImageId()
    {
        return $this->someline_image_id;
    }

    public function getImageUrl()
    {
        if ($this->file_size > 0) {
            return url('/image/' . $this->image_name);
        } else {
            return null;
        }
    }

}
