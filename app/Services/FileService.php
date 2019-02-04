<?php

namespace App\Services;

use Log;
use Storage;
use Config;

class FileService
{

    /**
     * Get local video path
     * @return string
     */
    public static function getVideoPath()
    {
        return Config::get('constant.SERIES_VIDEO_UPLOAD_PATH');
    }

    /**
     * Get local thumbs path
     * @return string
     */
    public static function getVideoThumbsPath()
    {
        return Config::get('constant.SERIES_VIDEO_THUMB_UPLOAD_PATH');
    }

    /**
     * Get local temp path
     * @return string
     */
    public static function getVideoTempPath()
    {
        return Config::get('constant.SERIES_VIDEO_TEMP_UPLOAD_PATH');
    }

    /**
     * Generate random name
     *
     * @param string $ext
     * @return string | array
     */
    public static function generateFileName($ext)
    {
        try {
            if (is_string($ext)) {
                return str_random(20) . '.' . $ext;
            } else {
                Log::error('FileService: Invalid file extension found!');
                return [
                    'status' => 0,
                    'message' => 'FileService: Invalid file extension found!',
                ];
            }
        } catch (\Exception $e) {
            Log::error('FileService: Error while generating file name: ' . $e->getMessage());
            return [
                'status' => 0,
                'message' => $e->getMessage(),
            ];
        }
    }

    /**
     * Get filename by path
     *
     * @param string $filePath
     * @return string
     * @throws Exception
     */
    public static function getFileName($filePath)
    {
        try {
            if (is_string($filePath)) {
                return basename($filePath);
            } else {
                Log::error('FileService: Invalid file path found!');
                return [
                    'status' => 0,
                    'message' => 'FileService: Invalid file path found!',
                ];
            }
        } catch (\Exception $e) {
            Log::error('FileService: Error while getting file name: ' . $e->getMessage());
            return [
                'status' => 0,
                'message' => $e->getMessage(),
            ];
        }
    }

    /**
     * Change extention for $fileName
     *
     * @param string $fileName
     * @param string $extension
     * @return string | Array
     * @throws Exception
     */
    public static function replaceExtension($fileName, $extension)
    {
        try {
            if (is_string($fileName) && is_string($extension)) {
                $info = pathinfo($fileName);
                return $info['filename'] . '.' . $extension;
            } else {
                Log::error("FileService: Invalid filename or extension found while replace extension.");
                return [
                    'status' => 0,
                    'message' => "FileService: Invalid filename or extension found while replace extension.",
                ];
            }
        } catch (\Exception $e) {
            Log::error('FileService: Error while replacing file extension: ' . $e->getMessage());
            return [
                'status' => 0,
                'message' => $e->getMessage(),
            ];
        }
    }

    /**
     * Check folders
     */
    public static function checkFolders($fileType)
    {

        self::createIfNotExsist(self::getVideoThumbsPath());
        self::createIfNotExsist(self::getVideoPath());
        self::createIfNotExsist(self::getVideoTempPath());
    }

    /**
     * Create file/folder if not exsist
     * @param string $path
     * @throws InvalidArgumentException
     */
    public static function createIfNotExsist($path)
    {
        try {
            if (is_string($path)) {
                if ($path) {
                    $path = rtrim($path, '/');
                }

                $storage = Storage::disk('public');

                // Make original path
                if (!$storage->exists($path)) {
                    $storage->makeDirectory($path);
                }
            } else {
                Log::error("FileService: Invalid path while creating directory.");
                return [
                    'status' => 0,
                    'message' => "FileService: Invalid path while creating directory.",
                ];
            }
        } catch (\Exception $e) {
            Log::error('FileService: Error while creating directory.: ' . $e->getMessage());
            return [
                'status' => 0,
                'message' => $e->getMessage(),
            ];
        }
    }
}
