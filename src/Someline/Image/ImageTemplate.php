<?php

class ImageTemplate
{

    public $width = 0;
    public $height = 0;
    public $ratio = true;
    public $download = false;
    public $options = [
        'blur' => [
            'amount' => 1,
        ],
    ];

    /**
     * ImageTemplate constructor.
     * e.g:
     *      (width=0, height=500)       => heighten to 500
     *      (width=500, height=0)       => widen to 500
     *      (width=500, height=500)     => resize to 500 x 500
     *
     * @param $width
     * @param $height
     * @param bool $ratio
     * @param array $options
     * @param bool $download If TRUE, it will tell browser to download the image instead of showing it.
     */
    public function __construct($width, $height, $ratio = true, array $options = [], $download = false)
    {
        $this->width = $width;
        $this->height = $height;
        $this->ratio = $ratio;
        $this->options = $options;
        $this->download = $download;
    }


}