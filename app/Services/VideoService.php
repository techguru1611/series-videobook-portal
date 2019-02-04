<?php

namespace App\Services;

use App\Services\FFMpegService;
use App\Services\FileService;
use App\Services\VideoTranscodingService;
use Log;
use Storage;

class VideoService
{

    /**
     * @var string
     */
    private $fileName = null;

    /**
     * @var string
     */
    private $filePath = null;

    /**
     * @var string
     */
    private $fileThumbPath = null;

    /**
     * @var object
     */
    private $file = null;

    /**
     * @var object FFMpeg
     */
    private $ffmpeg = null;

    /**
     * Load video file datas
     *
     * @param string $filePath
     */
    public function load($filePath = '')
    {
        Log::info('VideoService: loading file...');
        try {
            if (!empty($filePath)) {
                $response = [];
                $response = $this->setFilePath($filePath);

                if (is_array($response)) {
                    Log::error('VideoService: Error while setting file path: ' . $filePath);
                    return $response;
                }
            }

            $fileExists = false;

            if (Storage::disk('public')->exists($this->filePath)) {
                $fileExists = true;
            }

            if ($fileExists) {
                $this->setFFMpeg();
            } else {
                Log::error('VideoService: Video file is not found: ' . $this->filePath);
                return [
                    'status' => 0,
                    'message' => 'Video file is not found!',
                ];
            }
            Log::info('VideoService: File loaded...');
        } catch (\Exception $e) {
            Log::error('VideoService: Error while loading video in FFMpeg: ' . $e->getMessage());
            return [
                'status' => 0,
                'message' => $e->getMessage(),
            ];
        }
    }

    /**
     * Save video file datas
     *
     * @param object $file
     * @param array $params contain ['originalPath']
     * @return object
     */
    public function save($file, $params)
    {
        Log::info('VideoService: START saving file...');
        $response = $this->setFile($file);

        if (is_array($response)) {
            return [
                'status' => $response['status'],
                'message' => $response['message'],
            ];
        }

        // Set temp names
        $tempFileName = FileService::generateFileName($this->file->getClientOriginalExtension());
        // Error while generating video file name
        if (is_array($tempFileName)) {
            return [
                'status' => $response['status'],
                'message' => $response['message'],
            ];
        }

        // Set video title
        $this->setFileName($this->file->getClientOriginalName());

        try {
            $storage = Storage::disk('public');

            // Make original path
            if (!$storage->exists($params['originalPath'])) {
                $storage->makeDirectory($params['originalPath']);
            }

            $originalPath = $params['originalPath'] . $tempFileName;

            $storage->put($originalPath, file_get_contents($this->file), 'public');

            $response = $this->setFilePath($originalPath);

            if (is_array($response)) {
                return [
                    'status' => $response['status'],
                    'message' => $response['message'],
                ];
            }

            // Start - Save into database

            // End - Save into database
            Log::info('VideoService: File Saved: ' . $this->filePath);
        } catch (\Exception $e) {
            Log::error('VideoService: Error while saving video: ' . $e->getMessage());
            return [
                'status' => 0,
                'message' => $e->getMessage(),
            ];
        }

        Log::info('VideoService: END saving file.');

        $response = [];
        $response = $this->load();
        // Error while loading file
        if (is_array($response)) {
            return [
                'status' => $response['status'],
                'message' => $response['message'],
            ];
        }

        // Create thumbnail
        $response = [];
        $response = $this->ffm()->createThumb($params['imageOriginalPath']);

        if (isset($response['status']) && $response['status'] == 0) {
            return [
                'status' => $response['status'],
                'message' => $response['message'],
            ];
        }

        $this->fileThumbPath = $response['thumb'];

        return [
            'status' => 1,
            'message' => 'Video uploaded.',
            'filename' => $this->filePath,
        ];
    }

    /**
     * Transcode video
     *
     * @return object
     * @throws Exception
     */
    public function transcode(array $info = array())
    {
        Log::info('VideoService: START transcoding file.');

        $vtService = new VideoTranscodingService();
        $response = $vtService->transcode($info);

        if ($response['status'] === 0) {
            Log::info('VideoService: The transcode has failed.');

            return [
                'status' => 0,
                'message' => $response['message'],
            ];
        }

        Log::info('VideoService: The transcode has completed successfully.');

        Log::info('VideoService: END transcoding file.');

        return [
            'status' => 1,
            'message' => 'Video transcoded.',
            'video_url' => $response['video_url'],
            'temp_video_url' => $response['temp_video_url'],
            'thumb_url' => $response['thumb_url'],
            'file_size' => $response['file_size'],
            'file_name' => $response['file_name'],
        ];
    }

    /**
     * Get File name
     *
     * @return string
     */
    public function getFileName()
    {
        return $this->fileName;
    }

    /**
     * Set File name
     *
     * @param string $fileName
     * @return \App\Services\VideoService
     */
    private function setFileName($fileName)
    {
        try {
            if (is_string($fileName)) {
                $this->fileName = $fileName;
                return $this;
            } else {
                Log::error('VideoService: Error while setting file name!');
                return [
                    'status' => 0,
                    'message' => 'Error while setting file name!',
                ];
            }
        } catch (\Exception $e) {
            Log::error('VideoService: Error while setting file name: ' . $e->getMessage());
            return [
                'status' => 0,
                'message' => $e->getMessage(),
            ];
        }
    }

    /**
     * Get File path
     *
     * @return string
     */
    public function getFilePath()
    {
        return $this->filePath;
    }

    /**
     * Set File path
     *
     * @param string $filePath
     * @return \App\Services\VideoService
     */
    private function setFilePath($filePath)
    {
        try {
            if (is_string($filePath)) {
                $this->filePath = $filePath;
                return $this;
            } else {
                Log::error('VideoService: Error while setting file path!');
                return [
                    'status' => 0,
                    'message' => 'Error while setting file path!',
                ];
            }
        } catch (\Exception $e) {
            Log::error('VideoService: Error while setting file path: ' . $e->getMessage());
            return [
                'status' => 0,
                'message' => $e->getMessage(),
            ];
        }
    }

    /**
     * Set ThumbFile path
     *
     * @param string $fileThumbPath
     * @return \App\Services\VideoService
     * @throws InvalidArgumentException
     */
    public function setThumbFilePath($fileThumbPath)
    {
        try {
            if (is_string($fileThumbPath)) {
                $this->fileThumbPath = $fileThumbPath;
                return $this;
            } else {
                Log::error('VideoService: Invalid thumb file path found.');
                return [
                    'status' => 0,
                    'message' => 'VideoService: Invalid thumb file path found.',
                ];
            }
        } catch (\Exception $e) {
            Log::error('VideoService: Error while setting thumb file path: ' . $e->getMessage());
            return [
                'status' => 0,
                'message' => $e->getMessage(),
            ];
        }
    }

    /**
     * Set File object
     *
     * @param string $file
     * @return \App\Services\VideoService
     */
    private function setFile($file)
    {
        try {
            if (is_object($file)) {
                $this->file = $file;
                return $this;
            } else {
                Log::error('VideoService: Video file is not found!');
                return [
                    'status' => 0,
                    'message' => 'VideoService: Video file is not found!',
                ];
            }
        } catch (\Exception $e) {
            Log::error('VideoService: Error while set file: ' . $e->getMessage());
            return [
                'status' => 0,
                'message' => $e->getMessage(),
            ];
        }
    }

    /**
     * Set FFMpeg container
     */
    private function setFFMpeg()
    {
        try {
            Log::info('VideoService: Init FFMpeg.');

            $FFMpeg = new FFMpegService($this->filePath);
            $FFMpeg->load();

            $this->ffmpeg = $FFMpeg;
        } catch (\Exception $e) {
            Log::error('VideoService: Error while set FFMpeg: ' . $e->getMessage());
        }
    }

    /**
     * FFMpeg container
     */
    public function ffm()
    {
        return $this->ffmpeg;
    }

    /**
     * Get main info
     */
    public function getInfo()
    {
        try {
            $info = [
                'filename' => $this->fileName,
                'file' => $this->filePath,
                'thumb' => $this->fileThumbPath,
                'fileextension' => pathinfo($this->filePath, PATHINFO_EXTENSION),
                'filesize' => Storage::disk('public')->size($this->filePath),
            ];

            $ffmProbeAttrs = [
                'width',
                'height',
                'duration',
                'bit_rate',
                'r_frame_rate',
                'codec_name',
                'profile',
                'level',
            ];

            $ffmProbe = [];
            if (null !== $this->ffm() && null !== $this->ffm()->getFFProbe()) {
                $probe = $this->ffm()->getFFProbe();
                foreach ($ffmProbeAttrs as $attr) {
                    $v = $probe->get($attr);
                    if (isset($v) && !empty($v)) {
                        $ffmProbe[$attr] = $v;
                    }
                }

                // make to float
                $ffmProbe['r_frame_rate'] = floatval($ffmProbe['r_frame_rate']);

                $ffmProbe['profile'] = isset($ffmProbe['profile']) ? $ffmProbe['profile'] : '';
                $ffmProbe['level'] = isset($ffmProbe['level']) ? $ffmProbe['level'] : 0;

                // Get rotation
                $rotation = 0;
                $tags = $probe->get('tags');
                if (isset($tags) && !empty($tags) && isset($tags['rotate'])) {
                    $rotation = $tags['rotate'];
                }
                $ffmProbe['rotation'] = $rotation;
            }

            if (null !== $this->ffm() && null !== $this->ffm()->getFFProbeFormat()) {
                $format = $this->ffm()->getFFProbeFormat();
                // Get codecid
                $codecid = '';
                $tags = $format->get('tags');
                if (isset($tags) && !empty($tags) && isset($tags['major_brand'])) {
                    $codecid = trim($tags['major_brand']);
                }
                $ffmProbe['codecid'] = $codecid;
            }

            $info = array_merge($info, $ffmProbe);

            return $info;
        } catch (\Exception $e) {
            Log::error("VideoService: Error while getting video info... ({$e->getMessage()})");
            return [
                'status' => 0,
                'message' => "VideoService: Error while getting video info... ({$e->getMessage()})",
            ];
        }
    }

    /**
     * set chunk file and create thumb
     *
     * @param $params
     * @return array
     */
    public function setChunkVideo($params){
        $this->setFileName($params['fileName']);

        $originalPath = $params['originalPath'] . $params['fileName'];
        $response = $this->setFilePath($originalPath);
        Log::info('VideoService: File Saved: ' . $this->filePath);

        if (is_array($response)) {
            return [
                'status' => $response['status'],
                'message' => $response['message'],
            ];
        }

        Log::info('VideoService: END saving file.');

        $response = [];
        $response = $this->load();
        // Error while loading file
        if (is_array($response)) {
            return [
                'status' => $response['status'],
                'message' => $response['message'],
            ];
        }

        // Create thumbnail
        $response = [];
        $response = $this->ffm()->createThumb($params['imageOriginalPath']);

        if (isset($response['status']) && $response['status'] == 0) {
            return [
                'status' => $response['status'],
                'message' => $response['message'],
            ];
        }

        $this->fileThumbPath = $response['thumb'];

        return [
            'status' => 1,
            'message' => 'Video uploaded.',
            'filename' => $this->filePath,
        ];
    }
}
