<?php

namespace App\Services;

use App\Services\FileService;
use FFMpeg;
use Imagick;
use Log;
use Storage;

class FFMpegService
{
    /**
     * @var object
     */
    private $file = null;

    /**
     * @var object $FFProbe
     */
    private $FFProbe = null;

    /**
     * @var object $FFProbe
     */
    private $FFProbeFormat = null;

    /**
     * @var object FFMpeg->open
     */
    private $FFMVideo = null;

    /**
     * Constructor
     *
     * @param array $filePath
     */
    public function __construct($file)
    {
        if (!empty($file)) {
            $this->setFile($file);
        }
    }

    /**
     * Set File object
     *
     * @param string $file
     * @return \App\Services\FFMpegService
     */
    private function setFile($file)
    {
        if (is_object($file) || is_string($file)) {
            $this->file = $file;
            return $this;
        }
    }

    /**
     * Get info datas by FFMpeg lib
     */
    public function load()
    {
        try {
            // Init FFMpeg
            $FFM = FFMpeg\FFMpeg::create(['timeout' => 0]);
            $FFP = FFMpeg\FFProbe::create();

            // FOR WINDOWS
            // $FFM = FFMpeg\FFMpeg::create([
            //     'ffmpeg.binaries' => 'C:/Program Files/ffmpeg-20160506-git-abb69a2-win64-static/bin/ffmpeg.exe', // the path to the FFMpeg binary
            //     'ffprobe.binaries' => 'C:/Program Files/ffmpeg-20160506-git-abb69a2-win64-static/bin/ffprobe.exe', // the path to the FFProbe binary
            //     'timeout' => 3600, // the timeout for the underlying process
            //     'ffmpeg.threads' => 12, // the number of threads that FFMpeg should use
            // ]);

            // FFProbe data
            // load info by file_path
            $FFPData = $FFM->getFFProbe()
                ->streams(Storage::disk('public')->url($this->file))
                ->videos()
                ->first();

            $FFPDataFormat = $FFM->getFFProbe()
                ->format(Storage::disk('public')->url($this->file));

            // load info by file_path
            $FFMVideo = $FFM->open(Storage::disk('public')->url($this->file));

            $this->FFProbe = $FFPData;
            $this->FFProbeFormat = $FFPDataFormat;
            $this->FFMVideo = $FFMVideo;
        } catch (\Exception $e) {
            Log::error('FFMpegService: Unable to load file: ' . $e->getMessage());
        }
    }

    /**
     * Get Probe data by FFMpeg
     *
     * @return object
     */
    public function getFFProbe()
    {
        return $this->FFProbe;
    }

    /**
     * Get Full Probe data by FFMpeg
     *
     * @return object
     */
    public function getFFProbeFormat()
    {
        return $this->FFProbeFormat;
    }

    /**
     * Get Video by FFMpeg Data
     *
     * @return object
     */
    public function getFFMVideo()
    {
        return $this->FFMVideo;
    }

    /**
     * Create thumbnail by FFMpeg Data
     *
     * @return array
     */
    public function createThumb($thumbPath)
    {
        try {
            $thumbFileName = FileService::generateFileName('jpg');

            $duration = 1;
            if (null !== $this->getFFProbe()) {
                $duration = $this->getFFProbe()->get('duration');
            }

            $fromSeconds = ($duration > 6) ? 5 : 1; // Create thumbnail at $fromSeconds

            // Make directory if not exist
            $storage = Storage::disk('public');

            // Make original path
            if (!$storage->exists($thumbPath)) {
                $storage->makeDirectory($thumbPath);
            }

            $this->getFFMVideo()
                ->frame(FFMpeg\Coordinate\TimeCode::fromSeconds($fromSeconds))
                ->save($storage->path($thumbPath . $thumbFileName));

            // thumb to move on S3
            $file = file_get_contents($storage->path($thumbPath . $thumbFileName));


            Log::info('File Move Start on S3');
            $moveToS3 = Storage::disk('s3')->put($thumbPath . $thumbFileName, (string) $file, 'public');

            Log::info('File Move Successfully on S3');

            if ($moveToS3){
                $storage->delete($thumbPath . $thumbFileName);
            }

            // $this->resizeThumb(public_path() . $thumbPath . $thumbFileName, false);
        } catch (\Exception $e) {
            Log::error('FFMpegService: Error while thumbnail creating...' . $e->getMessage());
            return [
                'status' => 0,
                'message' => 'Error while thumbnail creating...' . $e->getMessage(),
            ];
        }

        Log::info('FFMpegService: thumbnail created: ' . $thumbPath . $thumbFileName);
        return [
            'status' => 1,
            'message' => 'Thumbnail created',
            'thumb' => $thumbPath . $thumbFileName,
        ];
    }

    /**
     * Resize thumbnail
     *
     * @param string $thumbfile
     * @param bool $withBg
     */
    private function resizeThumb($thumbfile, $withBg = true)
    {
        try {
            Log::info("FFMpegService: Thumb resizing - Start");
            // aspect ration by default
            $ar = 16 / 9;

            // Get info by source
            list($width, $height, $type) = getimagesize($thumbfile);
            // Set pane vars
            $paneWidth = $width;
            $paneHeight = $paneWidth / $ar;

            // Position source on pane
            $paneX = $paneWidth - $width;
            $paneY = $paneHeight - $height;

            // Load source
            $thumb = new Imagick();
            $thumb->readImage($thumbfile);

            if ($withBg) {
                $scale = $width / $height;
                // Set dist vars
                $outWidth = $paneWidth;
                $outHeight = $outWidth / $scale;
                if ($height > $paneHeight) {
                    $outHeight = $paneHeight;
                    $outWidth = $outHeight * $scale;
                }

                // Position dist on pane
                $outX = $paneWidth - $outWidth;
                $outY = $paneHeight - $outHeight;

                // Resize source to dist
                $thumb->resizeImage($outWidth, $outHeight, Imagick::FILTER_LANCZOS, 1);

                $overlay = new Imagick(public_path() . '/overlay.png');
                $overlay->resizeImage($paneWidth, $paneHeight, Imagick::FILTER_LANCZOS, 1);

                // Create BG from source
                $thumbBg = new Imagick();
                $thumbBg->readImage($thumbfile);
                $thumbBg->blurImage(5, 3);
                // $thumbBg->negateImage(false);
                $thumbBg->cropimage($paneWidth, $paneHeight, -$paneX / 2, -$paneY / 2);

                $thumbBg->setImageColorspace($thumb->getImageColorspace());
                // Merge BG and dist
                $thumbBg->compositeimage($overlay, Imagick::COMPOSITE_DEFAULT, 0, 0);
                $thumbBg->compositeImage($thumb, Imagick::COMPOSITE_DEFAULT, $outX / 2, $outY / 2);

                // Save dist
                $thumbBg->writeImage($thumbfile);

                // Free up memory
                $thumbBg->clear();
                $thumbBg->destroy();
                $overlay->clear();
                $overlay->destroy();
            } else {
                // Crop source to dist
                $thumb->cropimage($paneWidth, $paneHeight, -$paneX / 2, -$paneY / 2);

                // Save dist
                $thumb->writeImage($thumbfile);
            }

            // Free up memory
            $thumb->clear();
            $thumb->destroy();
            Log::info("FFMpegService: Thumb resizing - End");
        } catch (\Exception $e) {
            Log::error("FFMpegService: Error while resizing thumb... ({$e->getMessage()})");
            return [
                'status' => 0,
                'message' => "FFMpegService: Error while resizing thumb... ({$e->getMessage()})",
            ];
        }
    }
}
