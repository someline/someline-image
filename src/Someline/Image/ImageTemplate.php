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

    /**
     * @return int
     */
    public function getWidth(): int
    {
        return $this->width;
    }

    /**
     * @return int
     */
    public function getHeight(): int
    {
        return $this->height;
    }

    /**
     * @return boolean
     */
    public function isRatio(): bool
    {
        return $this->ratio;
    }

    /**
     * @return boolean
     */
    public function isDownload(): bool
    {
        return $this->download;
    }

    /**
     * @return array
     */
    public function getOptions(): array
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
    public function isWiden(): bool
    {
        return $this->width > 0 && $this->height == 0;
    }

    /**
     * @return bool
     */
    public function isHeighten(): bool
    {
        return $this->width == 0 && $this->height > 0;
    }

    /**
     * @return bool
     */
    public function isResize(): bool
    {
        return $this->width > 0 && $this->height > 0;
    }

}