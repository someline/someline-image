<?php

namespace Someline\Image;

class ImageTemplate
{

    protected $width = 0;
    protected $height = 0;
    protected $ratio = true;
    protected $fit = true;
    protected $download = false;
    protected $options = [
        'blur' => [
            'amount' => 1,
        ],
    ];

    /**
     * ImageTemplate constructor.
     * e.g:
     *      (width=0, height=0)       => original
     *      (width=0, height=500)       => heighten to 500
     *      (width=500, height=0)       => widen to 500
     *      (width=500, height=500)     => resize to 500 x 500
     *
     * @param $width
     * @param $height
     * @param bool $ratio
     * @param bool $fit
     * @param array $options
     * @param bool $download If TRUE, it will tell browser to download the image instead of showing it.
     */
    public function __construct($width, $height, $ratio = true, $fit = true, array $options = [], $download = false)
    {
        $this->width = $width;
        $this->height = $height;
        $this->fit = $fit;
        $this->ratio = $ratio;
        $this->options = $options;
        $this->download = $download;
    }

    public static function __set_state($data)
    {
        $imageTemplate = new ImageTemplate($data['width'], $data['height']);
        $imageTemplate->fit = $data['fit'];
        $imageTemplate->ratio = $data['ratio'];
        $imageTemplate->options = $data['options'];
        $imageTemplate->download = $data['download'];
        return $imageTemplate;
    }

    /**
     * @return int
     */
    public function getWidth()
    {
        return $this->width;
    }

    /**
     * @return int
     */
    public function getHeight()
    {
        return $this->height;
    }

    /**
     * @return boolean
     */
    public function isRatio()
    {
        return $this->ratio;
    }

    /**
     * @return boolean
     */
    public function isFit()
    {
        return $this->fit;
    }

    /**
     * @return boolean
     */
    public function isDownload()
    {
        return $this->download;
    }

    /**
     * @return array
     */
    public function getOptions()
    {
        return $this->options;
    }

    /**
     * @param $name
     * @return mixed|null
     */
    public function getOption($name)
    {
        if (isset($this->options[$name])) {
            return $this->options[$name];
        } else {
            return null;
        }
    }

    /**
     * @return bool
     */
    public function isWiden()
    {
        return $this->width > 0 && $this->height == 0;
    }

    /**
     * @return bool
     */
    public function isHeighten()
    {
        return $this->width == 0 && $this->height > 0;
    }

    /**
     * @return bool
     */
    public function isResize()
    {
        return $this->width > 0 && $this->height > 0;
    }

    /**
     * @return bool
     */
    public function isOriginal()
    {
        return $this->width == 0 && $this->height == 0;
    }

}