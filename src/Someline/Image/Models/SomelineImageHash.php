<?php

namespace Someline\Image\Models;

use Someline\Base\Models\BaseModel;
use Someline\Models\Image\SomelineImage;

class SomelineImageHash extends BaseModel
{

    protected $table = 'someline_image_hashes';

    protected $primaryKey = 'someline_image_hash_id';

    protected $fillable = ['someline_image_id', 'file_sha1'];

    protected $hidden = [];

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function image()
    {
        return $this->belongsTo(SomelineImage::class, 'someline_image_id', 'someline_image_id');
    }

}
