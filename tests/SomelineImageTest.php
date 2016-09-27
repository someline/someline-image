<?php

use Someline\Image\ImageTemplate;
use Someline\Image\Models\SomelineImage;
use Someline\Image\SomelineImageService;
use Symfony\Component\HttpFoundation\File\UploadedFile;

class SomelineImageTest extends TestCase
{

    public function testHandleImageUsingSomelineImageService()
    {
        $file = new UploadedFile(base_path('/public/images/12745840728709.jpg'), '12745840728709.jpg', null, null, null, true);

        $somelineImageService = new SomelineImageService();
        $somelineImage = $somelineImageService->handleUploadedFile($file);
        print_r($somelineImage->toArray());
    }

    public function testShowImage()
    {
        /** @var SomelineImage $somelineImage */
        $somelineImage = SomelineImage::find(1);

        $somelineImageService = new SomelineImageService();
        $somelineImageService->showImage('original', $somelineImage->getImageName());
    }

}