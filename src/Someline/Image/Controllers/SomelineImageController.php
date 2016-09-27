<?php namespace Someline\Image\Controllers;

use Illuminate\Routing\Controller as BaseController;
use Someline\Image\SomelineImageService;

class SomelineImageController extends BaseController
{

    /**
     * @param $template
     * @param $image_name
     * @return mixed|\Symfony\Component\HttpFoundation\BinaryFileResponse
     */
    public static function showImage($template, $image_name)
    {
        $somelineImageService = new SomelineImageService();
        return $somelineImageService->showImage($template, $image_name);
    }

}
