<?php namespace Someline\Image;

use Carbon\Carbon;
use File;
use Image;
use Someline\Image\Models\SomelineImage;
use Someline\Image\Models\SomelineImageHash;
use Storage;
use StoreImageException;
use Symfony\Component\HttpFoundation\FileStoreImageException\FileNotFoundException;
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
     * @param UploadedFile $file
     * @param string $additionValidatorRule
     * @param bool $isAllowGIF
     * @return false|SomelineImage|null
     * @throws StoreImageException
     */
    public function handleUploadedFile(UploadedFile $file, $additionValidatorRule = '', $isAllowGIF = false)
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
            'image' => 'required|mimes:jpg,jpeg,bmp,png' . $mimes . '|max:12829' . $additionValidatorRule,
        ]);
        if ($validator->fails()) {
            throw new StoreImageException($validator->errors()->first());
        }

        // file info
        $path_with_name = $file->getPathname();
        $file_extension = $file->getClientOriginalExtension();
        return $this->storeImageFromPath($path_with_name, $file_extension, $isAllowGIF);
    }

    /**
     * @param $path_with_name
     * @param null $file_extension
     * @param bool $isAllowGIF
     * @return SomelineImage|null
     * @throws StoreImageException
     */
    public function storeImageFromPath($path_with_name, $file_extension = null, $isAllowGIF = false)
    {
        $file_extension = $file_extension ?: pathinfo($path_with_name, PATHINFO_EXTENSION);
        $is_file_gif = $file_extension == 'gif';
        $file_origin_size = filesize($path_with_name);
        $is_animated_gif = $is_file_gif && $this->isAnimatedGif($path_with_name);


        if (!File::exists($path_with_name)) {
            throw new FileNotFoundException($path_with_name);
        }

        // read exif info
        $exif = read_exif_data_safe($path_with_name);

        // passed validation and make image
        $image = Image::make($path_with_name);
        $originWidth = $image->getWidth();
        $originHeight = $image->getHeight();
        $image_file_size_kb = $file_origin_size / 1024;
        $is_allowed_animated_gif = $is_animated_gif && $image_file_size_kb < 3000 && $originWidth < 600 && $originHeight < 900;

        // save as jpg
        $default_file_extension = 'jpg';
        // save as gif if is allowed
        if ($isAllowGIF && $is_allowed_animated_gif) {
            $default_file_extension = 'gif';
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
        if (!File::exists(dirname($final_path_with_name))) {
            $isMadeDir = mkdir(dirname($final_path_with_name), 0777, true);
            if (!$isMadeDir) {
                throw new StoreImageException('Failed to make dir: ' . dirname($final_path_with_name));
            }
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
                if (File::move($path_with_name, $final_path_with_name)) {
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

        return $someline_image;
    }

    /**
     * @param $originImageEncodedData
     * @return null|SomelineImage
     */
    public function storeImageFromURLEncodedImageData($originImageEncodedData)
    {
        $exif = read_exif_data_safe($originImageEncodedData);
        $origin_image_data = SomelineImageService::convertDataURLEncodedToImageData($originImageEncodedData);
        $origin_image_sha1 = sha1($origin_image_data);
        return $this->storeImageFromMake($origin_image_data, $origin_image_sha1, $exif);
    }

    /**
     * @param $source
     * @param $file_sha1
     * @param $exif
     * @return SomelineImage|null
     * @throws StoreImageException
     */
    public function storeImageFromMake($source, $file_sha1, $exif)
    {
        try {
            $image = Image::make($source);
        } catch (StoreImageException $e) {
            throw new StoreImageException("SomelineImageService: Unable to make image [" + (string)$e + "]");
        }

        $is_allowed_animated_gif = false;

        // save as jpg
        $default_file_extension = 'jpg';
        $storage_path = $this->storagePath();
        $final_file_sha1 = null;
        if ($file_sha1 === FALSE || strlen($file_sha1) != 40) {
            throw new StoreImageException('Invalid SHA1 for file.');
        }

        // final
        $final_file_name = strtolower($file_sha1 . '.' . $default_file_extension);
        $final_path_with_name = $storage_path . $final_file_name;

        // check directory
        if (!SomelineFileService::autoCreateDirectory($final_path_with_name)) {
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
            $isExists = $this->saveImage($image, $exif,
                $final_path_with_name, $final_file_sha1, $final_file_size);
        }

        // stored and save to database
        $someline_image = null;
        if ($isExists) {
            $someline_image = $this->saveImageInfo($isSimilarExists, $someline_image_hash,
                $final_file_name, $is_allowed_animated_gif, $final_file_size, $image, $exif,
                $file_sha1, $final_file_sha1);
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
     * @param $image_type_templates
     * @param $image_type
     * @param $image_name
     * @param null $storage_path
     * @param array $image_options
     * @return mixed|\Symfony\Component\HttpFoundation\BinaryFileResponse
     */
    public function showImage($image_type_templates, $image_type, $image_name, $storage_path = null, $image_options = [])
    {
        $cache_minutes = 60 * 24 * 15; // 15 days
        $storage_path = $storage_path ?: $this->storagePath();
        $file_path = $storage_path . $image_name;

        // check exists
        $isExists = File::exists($file_path);
        if (!$isExists) {
            abort(404);
        }

        // if download
        if ($image_type == 'download' && isset($image_type_templates[$image_type])) {
            return response()->download($file_path);
        }


        // invalid type
        if (!isset($image_type_templates[$image_type])) {
            abort(404);
        }
        $image_size = $image_type_templates[$image_type];

        // convert to image
        $img = Image::cache(function ($image) use ($file_path, $image_size, $image_type, $image_options) {
            /** @var \Intervention\Image\Image $image */
            $image->make($file_path);

            if ($image_size !== false) {
                if ($image_size[0] == 0) { // e.g. [0, 500]: heighten to 500, and keep ratio
                    $image->heighten($image_size[1], function ($constraint) {
                        $constraint->aspectRatio();
                        $constraint->upsize();
                    });
                } elseif ($image_size[1] == 0) { // e.g. [500, 0]: widen to 500, and keep ratio
                    $image->widen($image_size[0], function ($constraint) {
                        $constraint->aspectRatio();
                        $constraint->upsize();
                    });
                } elseif (isset($image_size[2])) { // e.g. [500, 500, true]: resize to 500 x 500, and keep ratio
                    // remain aspect ratio and to its largest possible
                    $image->resize($image_size[0], $image_size[1], function ($constraint) {
                        $constraint->aspectRatio();
                        $constraint->upsize();
                    });
                } else {
                    $image->fit($image_size[0], $image_size[1]);
                }
            }

            if (!empty($image_options)) {
                if (!empty($image_options['blur'])) {
                    $options = $image_options['blur'];
                    $amount = $options['amount'];
                    $image->blur($amount);
                }
            }

        }, $cache_minutes, true);


        return $img->response()->setPublic()
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
        $image_quality = 75;

        // higher quality for small images
        if ($image_file_size_kb < 500) {
            $image_quality = 100;
        }

        // higher compression for large size images, 2000kb
        if ($image_file_size_kb > 2000) {
            $image_quality = 65;
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
        $isExists = File::exists($final_path_with_name);
        return $isExists;
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

}