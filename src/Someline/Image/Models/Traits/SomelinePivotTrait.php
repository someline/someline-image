<?php

namespace Someline\Image\Models\Traits;

use Illuminate\Database\Eloquent\Relations\MorphToMany;
use Someline\Models\Image\SomelineImage;

trait SomelinePivotTrait
{

    /**
     * @return mixed
     */
    public function getPivot()
    {
        return $this->pivot;
    }

    /**
     * @return bool
     */
    public function hasPivot()
    {
        return !empty($this->getPivot());
    }

    /**
     * @return mixed|null
     */
    public function getPivotParent()
    {
        if ($this->hasPivot()) {
            return $this->getPivot()->parent;
        } else {
            return null;
        }
    }

}