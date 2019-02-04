<?php

namespace App\Services;

use File;
use Illuminate\Support\Facades\Storage;
use Intervention\Image\Facades\Image;
use Log;
use Config;

class ImageUpload
{

    /**
     * To upload image with creating thumb
     * @param File $file
     * @param array $params contain ['originalPath', 'thumbPath', 'thumbHeight', 'thumbWidth', 'previousImage']
     */
    public static function uploadWithThumbImage($file, $params)
    {
        try {
            if (!empty($file) && !empty($params)) {
                $extension = $file->getClientOriginalExtension();
                $name = str_random(20) . '.' . $extension;
                $storage = Storage::disk(Config::get('constant.FILESYSTEM_DRIVER'));

                // Make original path
                if (!$storage->exists($params['originalPath'])) {
                    $storage->makeDirectory($params['originalPath']);
                }

                // Make thumb path
                if (!$storage->exists($params['thumbPath'])) {
                    $storage->makeDirectory($params['thumbPath']);
                }

                $originalPath = $params['originalPath'] . $name;
                $thumbPath = $params['thumbPath'] . $name;
                
                // Store original image
                $storage->put($originalPath, file_get_contents($file), 'public');


                if (Image::make($file)->height() > Image::make($file)->width()){
                    $thumb = Image::make($file)->resize(null,$params['thumbHeight'], function ($constraint) {
                        $constraint->aspectRatio();
                    })->encode($extension);
                } else{
                    $thumb = Image::make($file)->resize($params['thumbWidth'], null, function ($constraint) {
                        $constraint->aspectRatio();
                    })->encode($extension);
                }

                // Store thumb image
                /*$thumb = Image::make($file)->resize(null, $params['thumbHeight'], function ($constraint) {
                    $constraint->aspectRatio();
                })->crop($params['thumbWidth'], $params['thumbHeight'])->encode($extension);*/

                $storage->put($thumbPath, (string) $thumb, 'public');

                // Delete previous image
                if ($params['previousImage'] != '') {
                    $originalImage = $params['originalPath'] . $params['previousImage'];
                    $thumbImage = $params['thumbPath'] . $params['previousImage'];
                    if ($storage->exists($originalImage)) {
                        $storage->delete($originalImage);
                    }
                    if ($storage->exists($thumbImage)) {
                        $storage->delete($thumbImage);
                    }
                }
                return [
                    'imageName' => $name,
                ];
            }
        } catch (\Exception $e) {
            Log::error(strtr(trans('log-messages.IMAGE_UPLOAD_ERROR_MESSAGE'), [
                '<Message>' => $e->getMessage(),
            ]));
            return false;
        }
    }

    public static function uploadVideo($file, $params)
    {
        try {
            if (!empty($file) && !empty($params)) {
                $extension = $file->getClientOriginalExtension();
                $name = str_random(20) . '.' . $extension;
                $size = $file->getClientSize();
                $storage = Storage::disk(Config::get('constant.FILESYSTEM_DRIVER'));

                // Make original path
                if (!$storage->exists($params['originalPath'])) {
                    $storage->makeDirectory($params['originalPath']);
                }

                $originalPath = $params['originalPath'] . $name;

                // Store original video
                $storage->put($originalPath, file_get_contents($file), 'public');

                //Delete previous video
                if ($params['previousVideo'] != '') {
                    $originalVideo = $params['originalPath'] . $params['previousVideo'];
                    if ($storage->exists($originalVideo)) {
                        $storage->delete($originalVideo);
                    }
                }
                return [
                    'videoName' => $name,
                    'size' => $size,
                    
                ];
            }
        } catch (\Exception $e) {
            Log::error(strtr(trans('log-messages.ADVERTISEMENT_VIDEO_UPLOAD_ERROR_MESSAGE'), [
                '<Message>' => $e->getMessage(),
            ]));
            return false;
        }
    }

    /**
     * local upload video 
     * as per use only testing 
     */
    public static function uploadVideoApi($file, $params)
    {
        try {
            if (!empty($file) && !empty($params)) {
                // get extension for video
                $extension = $file->getClientOriginalExtension();
                // change video name using random function
                $name = str_random(20) . '.' . $extension;
                // get video size
                $size = $file->getClientSize();
                // set path for storing file
                $storage = Storage::disk(Config::get('constant.FILESYSTEM_DRIVER'));
                // Make original path
                if (!$storage->exists($params['originalPath'])) {
                    $storage->makeDirectory($params['originalPath']);
                }
                $originalPath = $params['originalPath'] . $name;
                // Store original video
                $storage->put($originalPath, file_get_contents($file), 'public');
                return [
                    'videoName' => $name,
                    'size' => $size
                ];
            }
        } catch (\Exception $e) {
            Log::error(strtr(trans('log-messages.ADVERTISEMENT_VIDEO_UPLOAD_ERROR_MESSAGE'), [
                '<Message>' => $e->getMessage(),
            ]));
            return false;
        }
    }
}
