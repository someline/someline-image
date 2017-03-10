<?php

namespace Someline\Image\Models;

use Someline\Base\Models\BaseModel;

class SomelineImageHash extends BaseModel
{

    protected $table = 'someline_image_hashes';

    protected $primaryKey = 'someline_image_hash_id';

    protected $fillable = ['someline_image_id', 'file_sha1'];

    protected $hidden = [];

    public function image()
    {
        return $this->belongsTo(SomelineImage::class, 'someline_image_id', 'someline_image_id');
    }

}
