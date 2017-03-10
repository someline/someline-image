<?php

use Someline\Image\ImageTemplate;
use Someline\Image\Models\SomelineImage;
use Someline\Image\SomelineImageService;
use Someline\Models\Foundation\User;
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

    public function testHasImageables()
    {

        $somelineImage = SomelineImage::find(1);

        /** @var User $user */
        $user = User::find(1);

        // save image relations via save
        $user->images()->save($somelineImage, ['type' => 'cover', 'data' => json_encode('a')]);

        // save image relations via attach
        $user->images()->attach([1], ['type' => 'cover', 'data' => json_encode('a')]);

        // update image relations via sync
        $user->images()->sync([1]);

        // set as main image
        $user->setAsMainImage($somelineImage);

        // set as type main image
        $user->setAsTypeMainImage('cover', $somelineImage);

        // get all images
        print_r($user->getImages()->toArray());

        // get first main image
        print_r($user->getMainImage()->toArray());

        // get all main images
        print_r($user->getMainImages()->toArray());

        // get all type images
        print_r($user->getTypeImages('cover')->toArray());

        // get all type images and are main images
        print_r($user->getTypeMainImages('cover')->toArray());
    }
}