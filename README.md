# Someline Image Service

[![Latest Version](https://img.shields.io/github/release/someline/someline-image.svg?style=flat-square)](https://github.com/someline/someline-image/releases)
[![Software License](https://img.shields.io/badge/license-MIT-brightgreen.svg?style=flat-square)](LICENSE.md)
[![Total Downloads](https://img.shields.io/packagist/dt/someline/someline-image.svg?style=flat-square)](https://packagist.org/packages/someline/someline-image)

Someline Image is a helper service to handle uploaded images and store images without duplicates. 

Build for Laravel and [Someline Starter](https://starter.someline.com). 

## Install

### Via Composer

Install composer package to your laravel project

``` bash
composer require someline/someline-image
```

Add Service Provider to `config/app.php`

``` php
    'providers' => [
        ...
        Someline\Image\SomelineImageServiceProvider::class,
        ...
    ],
```

Publishing config file. 

``` bash
php artisan vendor:publish
```

After published, config file for Rest Client is `config/someline-image.php`, you will need to config it to use Rest Client.

## Usage

#### Routes

``` php
Route::get('/image/{name}', 'ImageController@showOriginalImage');
Route::post('/image', 'ImageController@postImage');
```

#### Many Imageables

Use on the Model:
```php
    use SomelineHasImageablesTrait;
```

Usage:

``` php

/** @var SomelineImage $somelineImage */
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

```

#### Sample Controller File

`app/Http/Controllers/ImageController.php`

``` php
<?php namespace App\Http\Controllers;

use Exception;
use Illuminate\Http\Request;
use Someline\Image\Controllers\SomelineImageController;
use Someline\Models\Image\SomelineImage;
use Someline\Image\SomelineImageService;

class ImageController extends Controller
{

    public function postImage(Request $request)
    {
        $somelineImageService = new SomelineImageService();
        $file = $request->file('image');

        $somelineImage = null;
        try {
            /** @var SomelineImage $somelineImage */
            $somelineImage = $somelineImageService->handleUploadedFile($file);
        } catch (Exception $e) {
            return 'Failed to save: ' . $e->getMessage();
        }

        if (!$somelineImage) {
            return 'Failed to save uploaded image.';
        }

        $somelineImageId = $somelineImage->getSomelineImageId();
        return 'Saved: ' . $somelineImage->getImageUrl();
    }

    public function showOriginalImage($image_name)
    {
        return SomelineImageController::showImage('original', $image_name);
    }

}
```

## Testing

``` bash
phpunit
```

## Contributing

Please see [CONTRIBUTING](https://github.com/someline/someline-image/blob/master/CONTRIBUTING.md) for details.

## Credits

- [Libern](https://github.com/libern)
- [Someline](https://github.com/someline)
- [All Contributors](https://github.com/someline/someline-image/contributors)

## License

The MIT License (MIT). Please see [License File](LICENSE.md) for more information.
