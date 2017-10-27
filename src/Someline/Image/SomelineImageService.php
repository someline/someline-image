<?php

namespace Someline\Image;

use Carbon\Carbon;
use File;
use Image;
use Someline\Image\Exception\SomelineImageException;
use Someline\Image\Exception\StoreImageException;
use Someline\Image\Models\SomelineImageHash;
use Someline\Models\Image\SomelineImage;
use Storage;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Validator;

class SomelineImageService
{

    /**
     * @param $key
     * @param null $default
     * @return mixed
     */
    public function getConfig($key, $default = null)
    {
        return config("someline-image.$key", $default);
    }

    /**
     * @return string
     */
    private function storagePath()
    {
        return $this->getConfig('storage_path');
    }

    /**
     * @return int
     */
    private function defaultQuality()
    {
        return (int)$this->getConfig('default_quality', 75);
    }

    /**
     * @return int
     */
    private function largeImageQuality()
    {
        return (int)$this->getConfig('large_image_quality', 65);
    }

    /**
     * @return boolean
     */
    private function isAlwaysOriginal()
    {
        return (boolean)$this->getConfig('always_original', true);
    }

    /**
     * @param UploadedFile $file
     * @param string $additionValidatorRule
     * @param bool $isAllowGIF
     * @param int $maxSizeAllowed
     * @param bool $isStorePNG
     * @return SomelineImage
     */
    public function handleUploadedFile(UploadedFile $file, $additionValidatorRule = '', $isAllowGIF = false, $maxSizeAllowed = 12829, $isStorePNG = false)
    {
        // check valid
        if (!$file->isValid()) {
            throw new StoreImageException($file->getErrorMessage());
        }

        // validate image
        $additionValidatorRule = !empty($additionValidatorRule) ? '|' . $additionValidatorRule : '';
        $mimes = $isAllowGIF ? ',gif' : '';
        /** @var Validator $validator */
        $validator = Validator::make(['image' => $file], [
            'image' => 'required|mimes:jpg,jpeg,bmp,png' . $mimes . '|max:' . $maxSizeAllowed . $additionValidatorRule,
        ]);
        if ($validator->fails()) {
            throw new StoreImageException($validator->errors()->first());
        }

        // file info
        $path_with_name = $file->getPathname();
        $file_extension = $file->getClientOriginalExtension();
        return $this->storeImageFromPath($path_with_name, $file_extension, $isAllowGIF, $isStorePNG);
    }

    /**
     * @param $path_with_name
     * @param null $file_extension
     * @param bool $isAllowGIF
     * @param bool $isStorePNG
     * @return null|SomelineImage
     */
    public function storeImageFromPath($path_with_name, $file_extension = null, $isAllowGIF = false, $isStorePNG = false)
    {
        $file_extension = $file_extension ?: pathinfo($path_with_name, PATHINFO_EXTENSION);
        $is_file_gif = $file_extension == 'gif';
        $is_file_png = $file_extension == 'png';
        $file_origin_size = filesize($path_with_name);
        $is_animated_gif = $is_file_gif && $this->isAnimatedGif($path_with_name);


        if (!File::exists($path_with_name)) {
            throw new StoreImageException("File not found: " . $path_with_name);
        }

        // read exif info
        $exif = read_exif_data_safe($path_with_name);

        // passed validation and make image
        $image = Image::make($path_with_name);
        $originWidth = $image->getWidth();
        $originHeight = $image->getHeight();
        $image_file_size_kb = $file_origin_size / 1024;
        $is_allowed_animated_gif = $is_animated_gif;
        $isStoreOriginImage = $this->isAlwaysOriginal();

        // save as jpg
        $default_file_extension = 'jpg';
        // save as gif if is allowed
        if ($isAllowGIF && $is_allowed_animated_gif) {
            $default_file_extension = 'gif';
        }
        if ($isStorePNG && $is_file_png) {
            $default_file_extension = 'png';
        }
        if ($isStoreOriginImage) {
            $default_file_extension = $file_extension;
        }
        $storage_path = $this->storagePath();
        $file_sha1 = sha1_file($path_with_name);
        $final_file_sha1 = null;
        if ($file_sha1 === FALSE) {
            throw new StoreImageException('Failed to create SHA1 for file: ' . $path_with_name);
        }

        // final
        $final_file_name = strtolower($file_sha1 . '.' . $default_file_extension);
        $final_path_with_name = $storage_path . $final_file_name;

        // check directory
        if (!self::autoCreateDirectory($final_path_with_name)) {
            throw new StoreImageException('Failed to make dir: ' . dirname($final_path_with_name));
        }

        // save
        $isExists = File::exists($final_path_with_name);
        $isSimilarExists = false;
        $someline_image_hash = null;
        $final_file_sha1 = null;
        $final_file_size = null;
        if ($isExists) {
            $final_file_sha1 = $file_sha1;
            $final_file_size = filesize($final_path_with_name);
        } else {
            $someline_image_hash = SomelineImageHash::where(['file_sha1' => $file_sha1])->first();
            $isExists = $isSimilarExists = $someline_image_hash ? true : false;
        }

        if (!$isExists) {
            if (($isStoreOriginImage) || ($isAllowGIF && $is_allowed_animated_gif) || ($isStorePNG && $is_file_png)) {
                if (File::copy($path_with_name, $final_path_with_name)) {
                    @chmod($final_path_with_name, 0666 & ~umask());
                } else {
                    throw new StoreImageException('Failed to move file to path: ' . $final_path_with_name);
                }
                $final_file_sha1 = sha1_file($final_path_with_name);
                $final_file_size = filesize($final_path_with_name);
                $isExists = File::exists($final_path_with_name);
            } else {
                $isExists = $this->saveImage($image, $exif,
                    $final_path_with_name, $final_file_sha1, $final_file_size);
            }
        }

        // stored and save to database
        $someline_image = null;
        if ($isExists) {
            $someline_image = $this->saveImageInfo($isSimilarExists, $someline_image_hash,
                $final_file_name, $is_allowed_animated_gif, $final_file_size, $image, $exif,
                $file_sha1, $final_file_sha1);
        }

        if (!$someline_image) {
            throw new StoreImageException('Failed to store image.');
        }

        return $someline_image;
    }

    /**
     * @param $originImageEncodedData
     * @param bool $isAllowGIF
     * @return null|SomelineImage
     */
    public function storeImageFromURLEncodedImageData($originImageEncodedData, $isAllowGIF = false)
    {
        $exif = read_exif_data_safe($originImageEncodedData);
        $is_gif = SomelineImageService::isDataURLEncodedImageGif($originImageEncodedData);
        $origin_image_data = SomelineImageService::convertDataURLEncodedToImageData($originImageEncodedData);
        $origin_image_sha1 = sha1($origin_image_data);
        return $this->storeImageFromMake($origin_image_data, $origin_image_sha1, $exif, $isAllowGIF, $is_gif);
    }

    /**
     * @param $source
     * @param $file_sha1
     * @param $exif
     * @param bool $isAllowGIF
     * @param bool $isGIF
     * @return SomelineImage
     */
    public function storeImageFromMake($source, $file_sha1, $exif, $isAllowGIF = false, $isGIF = false)
    {
        try {
            $image = Image::make($source);
        } catch (StoreImageException $e) {
            throw new StoreImageException("SomelineImageService: Unable to make image [" + (string)$e + "]");
        }

        $is_allowed_animated_gif = $isAllowGIF && $isGIF;

        // save as jpg
        $default_file_extension = 'jpg';
        // save as gif if is allowed
        if ($isAllowGIF && $is_allowed_animated_gif) {
            $default_file_extension = 'gif';
        }
        $storage_path = $this->storagePath();
        $final_file_sha1 = null;
        if ($file_sha1 === FALSE || strlen($file_sha1) != 40) {
            throw new StoreImageException('Invalid SHA1 for file.');
        }

        // final
        $final_file_name = strtolower($file_sha1 . '.' . $default_file_extension);
        $final_path_with_name = $storage_path . $final_file_name;

        // check directory
        if (!self::autoCreateDirectory($final_path_with_name)) {
            throw new StoreImageException('Failed to make dir: ' . dirname($final_path_with_name));
        }

        // save
        $isExists = File::exists($final_path_with_name);
        $isSimilarExists = false;
        $someline_image_hash = null;
        $final_file_sha1 = null;
        $final_file_size = null;
        if ($isExists) {
            $final_file_sha1 = $file_sha1;
            $final_file_size = filesize($final_path_with_name);
        } else {
            $someline_image_hash = SomelineImageHash::where(['file_sha1' => $file_sha1])->first();
            $isExists = $isSimilarExists = $someline_image_hash ? true : false;
        }

        if (!$isExists) {
            if ($isAllowGIF && $is_allowed_animated_gif) {
                if (file_put_contents($final_path_with_name, $source)) {
                    @chmod($final_path_with_name, 0666 & ~umask());
                } else {
                    throw new StoreImageException('Failed to move file to path: ' . $final_path_with_name);
                }
                $final_file_sha1 = sha1_file($final_path_with_name);
                $final_file_size = filesize($final_path_with_name);
                $isExists = File::exists($final_path_with_name);
            } else {
                $isExists = $this->saveImage($image, $exif,
                    $final_path_with_name, $final_file_sha1, $final_file_size);
            }
        }

        // stored and save to database
        $someline_image = null;
        if ($isExists) {
            $someline_image = $this->saveImageInfo($isSimilarExists, $someline_image_hash,
                $final_file_name, $is_allowed_animated_gif, $final_file_size, $image, $exif,
                $file_sha1, $final_file_sha1);
        }

        if (!$someline_image) {
            throw new StoreImageException('Failed to store image.');
        }

        return $someline_image;
    }

    /**
     * @param $filename
     * @return bool
     */
    public function isAnimatedGif($filename)
    {
        $file_contents = file_get_contents($filename);

        $str_loc = 0;
        $count = 0;

        // There is no point in continuing after we find a 2nd frame
        while ($count < 2) {
            $where1 = strpos($file_contents, "\x00\x21\xF9\x04", $str_loc);
            if ($where1 === FALSE) {
                break;
            }

            $str_loc = $where1 + 1;
            $where2 = strpos($file_contents, "\x00\x2C", $str_loc);
            if ($where2 === FALSE) {
                break;
            } else {
                if ($where1 + 8 == $where2) {
                    $count++;
                }
                $str_loc = $where2 + 1;
            }
        }

        // gif is animated when it has two or more frames
        return ($count >= 2);
    }

    /**
     * @param $image_name
     * @return mixed
     */
    public function getImageOrFail($image_name)
    {
        return SomelineImage::where('image_name', $image_name)->firstOrFail();
    }

    /**
     * @param $template_name The template to be used for showing
     * @param $image_name The image name for showing
     * @param $image_templates array Additional image templates, if same template exists, will be replaced
     * @param null $storage_path
     * @return mixed|\Symfony\Component\HttpFoundation\BinaryFileResponse
     */
    public function showImage($template_name, $image_name, array $image_templates = [], $storage_path = null)
    {
        $cache_minutes = 60 * 24 * 15; // 15 days
        $storage_path = $storage_path ?: $this->storagePath();
        $image_templates = array_merge($this->getConfig('image_templates', []), $image_templates);
        $file_path = $storage_path . $image_name;

        // check exists
        $isExists = File::exists($file_path);
        if (!$isExists) {
            abort(404);
        }

        // if is invalid type
        if (!isset($image_templates[$template_name])) {
            throw new SomelineImageException('Invalid template name supplied.');
        }

        /** @var ImageTemplate $imageTemplate */
        $imageTemplate = $image_templates[$template_name];

        // if download
        if ($imageTemplate->isDownload()) {
            return response()->download($file_path);
        }

        $file = File::get($file_path);
        $type = File::mimeType($file_path);

        // if gif or original
        if (str_contains($image_name, 'gif') || ($imageTemplate->isOriginal() && $imageTemplate->isEmptyOptions())) {
            $response = response($file, 200)
                ->header('Content-Type', $type);
        } else {

            // convert to image
            $img = Image::cache(function ($image) use ($file_path, $imageTemplate) {
                /** @var \Intervention\Image\Image $image */
                $image->make($file_path);

                // resize
                if (!$imageTemplate->isOriginal()) {
                    if ($imageTemplate->isHeighten()) {
                        $image->heighten($imageTemplate->getHeight(), function ($constraint) use ($imageTemplate) {
                            if ($imageTemplate->isRatio()) {
                                $constraint->aspectRatio();
                            }
                            $constraint->upsize();
                        });
                    } elseif ($imageTemplate->isWiden()) {
                        $image->widen($imageTemplate->getWidth(), function ($constraint) use ($imageTemplate) {
                            if ($imageTemplate->isRatio()) {
                                $constraint->aspectRatio();
                            }
                            $constraint->upsize();
                        });
                    } elseif ($imageTemplate->isResize()) {
                        // remain aspect ratio and to its largest possible
                        if ($imageTemplate->isFit()) {
                            $image->fit($imageTemplate->getWidth(), $imageTemplate->getHeight());
                        } else {
                            $image->resize($imageTemplate->getWidth(), $imageTemplate->getHeight(),
                                function ($constraint) use ($imageTemplate) {
                                    if ($imageTemplate->isRatio()) {
                                        $constraint->aspectRatio();
                                    }
                                    $constraint->upsize();
                                });
                        }
                    }
                }

                // blur
                $blur_option = $imageTemplate->getOption('blur');
                if (!empty($blur_option)) {
                    $amount = $blur_option['amount'];
                    $image->blur($amount);
                }

            }, $cache_minutes, true);

            $response = $img->response();

        }

        return $response->setPublic()
            ->setMaxAge(604800)
            ->setExpires(Carbon::now()->addDay(7));
    }

    /**
     * @param $image
     * @param $exif
     * @param $final_path_with_name
     * @param null $final_file_sha1
     * @param null $final_file_size
     * @return bool
     */
    public function saveImage($image, $exif, $final_path_with_name,
                              &$final_file_sha1 = null, &$final_file_size = null)
    {
        // set correct orientation
        if (!empty($exif) && is_array($exif)) {
            if (isset($exif['Orientation']) && $exif['Orientation'] != 1) {
                $orientation = $exif['Orientation'];
                $deg = 0;
                switch ($orientation) {
                    case 3:
                        $deg = 180;
                        break;
                    case 6:
                        $deg = 270;
                        break;
                    case 8:
                        $deg = 90;
                        break;
                }
                // rotate image
                $image->rotate($deg);
            }
        }

        // basic info
        $originWidth = $image->getWidth();
        $originHeight = $image->getHeight();
        $file_origin_size = $image->filesize();
        $image_file_size_kb = $file_origin_size / 1024;

        // default quality 75
        $image_quality = $this->defaultQuality();

        // higher quality for small images
        if ($image_file_size_kb < 500) {
            $image_quality = 100;
        }

        // higher compression for large size images, 2000kb
        if ($image_file_size_kb > 2000) {
            // quality 65
            $image_quality = $this->largeImageQuality();
        }

        // reduce dimension to max 5000px
        $maxDimensionAllow = 5000;
        if ($originWidth > $maxDimensionAllow || $originHeight > $maxDimensionAllow) {
            $newWidth = $newHeight = null;
            if ($originWidth >= $originHeight) {
                $newWidth = $maxDimensionAllow;
            } else {
                $newHeight = $maxDimensionAllow;
            }

            // resize
            $image->resize($newWidth, $newHeight, function ($constraint) {
                $constraint->aspectRatio();
                $constraint->upsize();
            });
        }

        // save as JPG
        $image->save($final_path_with_name, $image_quality);
        $final_file_sha1 = sha1_file($final_path_with_name);
        $final_file_size = filesize($final_path_with_name);
        $isSaved = File::exists($final_path_with_name);
        return $isSaved;
    }

    /**
     * @param $isSimilarExists
     * @param $someline_image_hash
     * @param $final_file_name
     * @param $is_allowed_animated_gif
     * @param $final_file_size
     * @param $image
     * @param $exif
     * @param $file_sha1
     * @param $final_file_sha1
     * @return SomelineImage
     */
    private function saveImageInfo($isSimilarExists, $someline_image_hash, $final_file_name, $is_allowed_animated_gif, $final_file_size, $image, $exif, $file_sha1, $final_file_sha1)
    {
        // check has someline image
        if ($isSimilarExists) {
            $someline_image = $someline_image_hash->image;
        } else {
            $someline_image = SomelineImage::where('image_name', $final_file_name)->first();
        }
        // if no record, then save
        if (!$someline_image) {
            // read all existing data into an array
            $exif_data = $exif && is_array($exif) ? json_encode_safe($exif) : null;

            // create
            $someline_image = SomelineImage::firstOrCreate([
                'image_name' => $final_file_name,
                'is_gif' => $is_allowed_animated_gif,
                'exif' => $exif_data,
                'file_size' => $final_file_size,
                'width' => $image->getWidth(),
                'height' => $image->getHeight(),
            ]);

            // add to image hash
            SomelineImageHash::firstOrCreate([
                'someline_image_id' => $someline_image->someline_image_id,
                'file_sha1' => $file_sha1,
            ]);
            if (!empty($final_file_sha1) && $final_file_sha1 != $file_sha1) {
                SomelineImageHash::firstOrCreate([
                    'someline_image_id' => $someline_image->someline_image_id,
                    'file_sha1' => $final_file_sha1,
                ]);
            }
        }
        return $someline_image;
    }

    public static function convertDataURLEncodedToImageData($encodedData)
    {
        try {
            $filteredData = substr($encodedData, strpos($encodedData, ",") + 1);
            $image_data = base64_decode($filteredData);
            return $image_data;
        } catch (StoreImageException $e) {
            \Log::error("SomelineImageService: Unable to convert to image [" + (string)$e + "]");
            return null;
        }
    }

    public static function isDataURLEncodedImageGif($encodedData)
    {
        try {
            $image_type = substr($encodedData, 0, strpos($encodedData, ","));
            $is_gif = str_contains($image_type, 'gif');
            return $is_gif;
        } catch (StoreImageException $e) {
            \Log::error("SomelineImageService: Unable to convert to image [" + (string)$e + "]");
            return false;
        }
    }

    /**
     * auto create directory if not exists
     * @param $path_name
     * @return bool
     */
    public static function autoCreateDirectory($path_name)
    {
        // check directory
        if (!File::exists(dirname($path_name))) {
            $isMadeDir = mkdir(dirname($path_name), 0777, true);
            if (!$isMadeDir) {
                return false;
            }
        }
        return true;
    }

    /**
     * @param integer $someline_image_id
     * @return \Intervention\Image\Image
     */
    public static function getImageFromSomelineImageId($someline_image_id)
    {
        $someline_image = SomelineImage::find($someline_image_id);
        if (is_null($someline_image)) {
            throw new SomelineImageException('Someline image record not found!');
        }
        $imageManager = new ImageManager(config('image'));
        $background_image = $imageManager->make($someline_image->getImagePath());
        return $background_image;
    }

}