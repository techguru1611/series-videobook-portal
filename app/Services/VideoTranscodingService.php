<?php

namespace App\Services;

use App\Services\FileService;
use Config;
use Log;
use Storage;

class VideoTranscodingService
{

    /**
     * @var array
     */
    private $presetFrames = [
        'standard' => [24, 25, 30],
        'high' => [48, 50, 60],
    ];

    /**
     * @var array
     */
    private $resolutions = [360, 480, 720, 1080, 1440, 2160];

    /**
     * @var array
     */
    private $presetBitRates = [
        'standard' => [ //(24, 25, 30)
            'SDR' => [
                '2160p' => [35000000, 45000000],
                '1440p' => 16000000,
                '1080p' => 8000000,
                '720p' => 5000000,
                '480p' => 2500000,
                '360p' => 1000000,
            ],
            'HDR' => [
                '2160p' => [44000000, 56000000],
                '1440p' => 20000000,
                '1080p' => 10000000,
                '720p' => 6500000,
            ],
        ],
        'high' => [ //(48, 50, 60)
            'SDR' => [
                '2160p' => [58000000, 68000000],
                '1440p' => 24000000,
                '1080p' => 12000000,
                '720p' => 7500000,
                '480p' => 4000000,
                '360p' => 1500000,
            ],
            'HDR' => [
                '2160p' => [66000000, 85000000],
                '1440p' => 30000000,
                '1080p' => 15000000,
                '720p' => 9500000,
            ],
        ],
    ];

    private $resolutionsByType = [
        '2160p' => ['3840', '2160'],
        '1440p' => ['2560', '1440'],
        '1080p' => ['1920', '1080'],
        '720p' => ['1280', '720'],
        '480p' => ['854', '480'],
        '360p' => ['640', '360'],
    ];

    /**
     * Transcode video
     *
     * @return object
     * @throws Exception
     */
    public function transcode(array $info = array())
    {
        $storage = Storage::disk('public');

        // Make original path
        if (!$storage->exists(Config::get('constant.SERIES_VIDEO_UPLOAD_PATH'))) {
            $storage->makeDirectory(Config::get('constant.SERIES_VIDEO_UPLOAD_PATH'));
        }

        try {
            $frameRate = round(floatval($info['frame_rate']));
            $frameRateType = $this->getFramerateType($frameRate);

            if (is_array($frameRateType)) {
                return [
                    'status' => $frameRateType['status'],
                    'message' => $frameRateType['message'],
                ];
            }

            $width = intval($info['current_width']);
            $height = intval($info['current_height']);
            $rotation = intval($info['rotation']);
            if ($width < $height && $rotation === 0) {
                // Rotate it if width < heigh
                $rotation = 90;
            }

            $resolutionType = $this->getResolutionType($width, $height);
            $resolution = $this->getResolution($resolutionType, $rotation);

            $bitRate = intval($info['bit_rate']);
            $expectedBitRate = $this->getExpectedBitrate($frameRateType, $resolutionType);
            $optimalBitRate = $this->getOptimalBitrate($bitRate, $expectedBitRate);

            $responseTr = $this->createVideo([
                'video_url' => $storage->url($info['video_url']),
                'video_name' => $info['video_name'],
                'resolution' => $resolution,
                'frame_rate' => $frameRate,
                'bit_rate' => $optimalBitRate,
                'codecid' => trim($info['codecid']),
                'codec_name' => trim($info['codec_name']),
                'profile' => strtolower(trim($info['profile'])),
                'level' => intval($info['level']),
            ]);

            if ($responseTr['status'] === 0) {
                return [
                    'status' => $responseTr['status'],
                    'message' => "VideoTranscodingService: Error while video transcoding: {$responseTr['message']}",
                ];
            }
            $filesize = $storage->size($responseTr['video_url']);
            $info['filesize'] = $filesize < 1 ? 1 : $filesize;

            return [
                'status' => 1,
                'message' => 'Video transcoded.',
                'video_url' => $responseTr['video_url'],
                'temp_video_url' => $info['video_url'],
                'thumb_url' => $info['thumb_url'],
                'file_size' => $info['filesize'],
                'file_name' => $responseTr['file_name'],
            ];
        } catch (\Exception $e) {
            Log::error("VideoTranscodingService: Error while video pretranscoding: {$e->getMessage()}");
            return [
                'status' => 0,
                'message' => "VideoTranscodingService: Error while video pretranscoding: {$e->getMessage()}",
            ];
        }
    }

    /**
     * Create video using params
     *
     * @param array
     * @throws Exception
     */
    private function createVideo($params)
    {
        try {
            $default = [
                'format' => 'mp4',
                'video_codec' => 'libx264', // libx264, libx265
                'audio_codec' => 'aac', // aac, libvorbis
                'preset' => 'veryfast', // ultrafast, superfast, veryfast, faster, fast, medium (default), slow and veryslow
            ];

            // Get FileName and change extension
            $fileName = FileService::replaceExtension($params['video_name'], $default['format']);

            if (is_array($fileName)) {
                return [
                    'status' => $fileName['status'],
                    'message' => $fileName['message'],
                ];
            }

            $cmd = "ffmpeg ";
            $cmd .= "-i {$params['video_url']} ";
            $cmd .= "-vf \"scale={$params['resolution']}:force_original_aspect_ratio=decrease,pad={$params['resolution']}:(ow-iw)/2:(oh-ih)/2\" ";
            // $cmd .= "-s {$params['resolution']} ";
            $cmd .= "-f {$default['format']} ";
            $cmd .= "-c:v {$default['video_codec']} ";
            $cmd .= "-preset {$default['preset']} ";
            if ($params['codec_name'] !== 'mjpeg') {
                $profile = '';
                if (in_array($params['profile'], ['high', 'main'])) {
                    $profile = "-profile:v {$params['profile']} ";
                } elseif ($params['profile'] !== '') {
                    $profile = "-profile:v baseline ";
                    $cmd .= "-b:v {$params['bit_rate']} ";
                }

                $cmd .= $profile;

                if ($params['level'] >= 42) {
                    $cmd .= "-level 4.2 ";
                } elseif ($params['level'] >= 41) {
                    $cmd .= "-level 4.1 ";
                } elseif ($params['level'] >= 40) {
                    $cmd .= "-level 4.0 ";
                } elseif ($params['level'] >= 31) {
                    $cmd .= "-level 3.1 ";
                } elseif ($params['level'] > 0) {
                    $cmd .= "-level 3.0 ";
                }
            }
            $cmd .= "-movflags +faststart ";
            $cmd .= "-threads 2 ";
            $cmd .= "-thread_type 2 ";
            $cmd .= "-c:a {$default['audio_codec']} ";
            $cmd .= "-strict experimental ";
            $cmd .= "-ac 2 ";
            $cmd .= "-y " . Storage::disk('public')->path(Config::get('constant.SERIES_VIDEO_UPLOAD_PATH') . $fileName);

            exec($cmd);

            return [
                'status' => 1,
                'message' => 'Video transcoded.',
                'video_url' => Config::get('constant.SERIES_VIDEO_UPLOAD_PATH') . $fileName,
                'file_name' => $fileName,
            ];

        } catch (\Exception $e) {
            Log::error("VideoTranscodingService: Error while video transcoding: {$e->getMessage()}");
            return [
                'status' => 0,
                'message' => "VideoTranscodingService: Error while video transcoding: {$e->getMessage()}",
            ];
        }
    }

    /**
     * Get type of framerate video
     *
     * @param float
     * @return string
     * @throws Exception
     */
    private function getFramerateType($frameRate)
    {
        try {
            if (is_float($frameRate)) {
                $type = 'standard';
                foreach ($this->presetFrames as $key => $values) {
                    if ($frameRate >= $values[0] && $frameRate <= end($values)) {
                        $type = $key;
                    }
                }

                return $type;
            } else {
                Log::error('VideoTranscodingService: Invalid frame rate type in processing.');
                return [
                    'status' => 0,
                    'message' => 'Invalid frame rate type in processing.',
                ];
            }
        } catch (\Exception $e) {
            Log::error('VideoTranscodingService: Error while getting frame rate type in processing: ' . $e->getMessage());
            return [
                'status' => 0,
                'message' => $e->getMessage(),
            ];
        }
    }

    /**
     * Get type of resolution video
     *
     * @param int
     * @param int
     * @return string
     * @throws Exception
     */
    private function getResolutionType($width, $height)
    {
        try {
            if (is_int($width) && is_int($height)) {
                $r = $height;
                if ($height > $width) {
                    $r = $width;
                }

                $r = intval($r);

                $type = $this->resolutions[0] . 'p';
                foreach ($this->resolutions as $_r) {
                    if ($r >= $_r) {
                        $type = $_r . 'p';
                    }
                }

                return $type;
            } else {
                Log::error('VideoTranscodingService: Invalid resolution type in processing.');
                return [
                    'status' => 0,
                    'message' => 'Invalid resolution type in processing.',
                ];
            }
        } catch (\Exception $e) {
            Log::error('VideoTranscodingService: Error while getting resolution type in processing: ' . $e->getMessage());
            return [
                'status' => 0,
                'message' => $e->getMessage(),
            ];
        }
    }

    /**
     * Get resolution video
     *
     * @param string
     * @param int
     * @return string
     * @throws InvalidArgumentException
     */
    private function getResolution($resolutionType, $rotation = 0)
    {
        try {
            if (is_string($resolutionType) && is_integer($rotation)) {
                $resolution = $this->resolutionsByType[$resolutionType];
                if ($rotation == 90 || $rotation == 270) {
                    return $resolution[1] . ":" . $resolution[0];
                }
                return $resolution[0] . ":" . $resolution[1];
            } else {
                Log::error('VideoTranscodingService: Invalid resolution in processing.');
                return [
                    'status' => 0,
                    'message' => 'Invalid resolution in processing.',
                ];
            }
        } catch (\Exception $e) {
            Log::error('VideoTranscodingService: Error while getting resolution in processing: ' . $e->getMessage());
            return [
                'status' => 0,
                'message' => $e->getMessage(),
            ];
        }
    }

    /**
     * Get expected of bitrate video
     *
     * @param string
     * @param string
     * @return mix
     * @throws Exception
     */
    private function getExpectedBitrate($frameRateType, $resolutionType)
    {
        try {
            if (is_string($frameRateType) && is_string($resolutionType)) {
                return $this->presetBitRates[$frameRateType]['SDR'][$resolutionType];
            } else {
                Log::error('VideoTranscodingService: Invalid frame rate or bit rate in processing.');
                return [
                    'status' => 0,
                    'message' => 'Invalid frame rate or bit rate in processing.',
                ];
            }
        } catch (\Exception $e) {
            Log::error('VideoTranscodingService: Error while getting frame rate or bit rate in processing: ' . $e->getMessage());
            return [
                'status' => 0,
                'message' => $e->getMessage(),
            ];
        }
    }

    /**
     * Get optimal of bitrate video
     *
     * @param int
     * @param mix
     * @return int
     * @throws Exception
     */
    private function getOptimalBitrate($frameRateCurrent, $expectedBitRate)
    {
        try {
            if (is_integer($frameRateCurrent) && (is_array($expectedBitRate) || is_integer($expectedBitRate))) {
                if (is_array($expectedBitRate)) {
                    if (end($expectedBitRate) < $frameRateCurrent) {
                        return end($expectedBitRate);
                    }

                    return $this->getOptimalBitrate($frameRateCurrent, $expectedBitRate[0]);
                } else {
                    if ($frameRateCurrent < $expectedBitRate) {
                        return $frameRateCurrent;
                    }

                    return $expectedBitRate;
                }
            } else {
                Log::error('VideoTranscodingService: Invalid frame rate or bit rate while getting optimal bitrate in processing.');
                return [
                    'status' => 0,
                    'message' => 'Invalid frame rate or bit rate while getting optimal bitrate in processing.',
                ];
            }
        } catch (\Exception $e) {
            Log::error('VideoTranscodingService: Error while getting frame rate or bit rate in optimal bitrate in processing: ' . $e->getMessage());
            return [
                'status' => 0,
                'message' => $e->getMessage(),
            ];
        }
    }
}
