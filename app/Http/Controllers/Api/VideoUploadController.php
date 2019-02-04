<?php

namespace App\Http\Controllers\Api;

use App\Http\Resources\VideoSeriresAuthorVideoResource;
use App\Jobs\TranscodeVideo;
use App\Models\VideoBook;
use App\Models\VideoBookVideo;
use App\Services\FileService;
use App\Services\VideoService;
use Carbon\Carbon;
use function GuzzleHttp\Psr7\str;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Models\VideoUpload;
use App\Services\ImageUpload;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Response;
use Files;

class VideoUploadController extends Controller
{
    public function __construct()
    {
        $this->videoOriginalImagePath = Config::get('constant.VIDEO_UPLOAD_TEST_ORIGINAL_UPLOAD_PATH');
        $this->videoTempPath = Config::get('constant.SERIES_VIDEO_TEMP_UPLOAD_PATH');
        $this->videoPath = Config::get('constant.SERIES_VIDEO_UPLOAD_PATH');
        $this->videoThumbPath = Config::get('constant.SERIES_VIDEO_THUMB_UPLOAD_PATH');
        $this->seriesThumbPath = Config::get('constant.VIDEO_SERIES_THUMB_PHOTO_UPLOAD_PATH');
    }

    public function upload(Request $request){
        DB::beginTransaction();
        try{
            // Rule Start Validation check
            $rule = [  
                'video_title' => 'required|max:100',
                'video_path' => 'required|mimes:mp4|max:409600',
                'status' => ['required', Rule::in([Config::get('constant.ACTIVE_FLAG'), Config::get('constant.INACTIVE_FLAG')])],
            ];

            $validator = Validator::make($request->all() , $rule);
            
            if ($validator->fails()) {
                return response()->json([
                    'status' => 0,
                    'message' => $validator->messages()->all()[0],
                ]);
            }
            // Rule End Validation check

            $postData = $request->only('video_title', 'video_path','size','status');
            
            // check get request value in file not null to go upload process
            if (!empty($request->file('video_path')) && $request->file('video_path')->isValid()) {
                $params = [
                    'originalPath' => $this->videoOriginalImagePath,
                ];
                $videoUpload = ImageUpload::uploadVideoApi($request->file('video_path'), $params);
                if ($videoUpload === false) {
                    DB::rollback();
                    return response()->json([
                        'status' => 0,
                        'message' => trans('log-messages.UPLOAD_API_VIDEO_UPLOAD_ERROR_MESSAGE'),
                    ]);                    
                    // Log the error
                    Log::error(strtr(trans('log-messages.UPLOAD_API_VIDEO_UPLOAD_ERROR_MESSAGE'), [
                        '<Message>' => $e->getMessage(),
                    ]));
                }
                $postData['video_path'] = $videoUpload['videoName'];
                $postData['size'] = $videoUpload['size'];
            }
            // create Object for video upload
            $uploadVideo = new VideoUpload($postData);
            $uploadVideo->save();
            DB::commit();
            return response()->json([
                'status' => 1,
                'message' => 'video uploaded successfully',
                'data' => []
            ]);

        } catch(\Exception $e){
            DB::rollback();
            Log::error(strtr(trans('log-messages.DEFAULT_ERROR_MESSAGE'), [
                '<Message>' => $e->getMessage(),
            ]));
            return response()->json([
                'status' => 0,
                'message' => trans('api-message.DEFAULT_ERROR_MESSAGE'),
            ], 500);
        }
    }

    /**
     * get Video Details
     *
     * @param $id
     * @param $video_id
     * @return \Illuminate\Http\JsonResponse
     */
    public function getVideo($video_id){
        try {
            $video = VideoBookVideo::find($video_id);

            if (!$video){
                return response()->json([
                    'status' => 0,
                    'message' => trans('api-message.VIDEO_NOT_FOUND'),
                ]);

            }
            $address = Storage::disk('public')->path($this->videoTempPath.$video->path);
            $size=0;
            if(file_exists($address)){
                $size=filesize($address);
            }

            if ($size == 0){
                $progressPercent = "0";
            } else{
                $progressPercent = ($size/($video->size))*100;
            }

            $video->chunkPointer = $size;
            $video->progressPercent = number_format($progressPercent,2,'.','');

            return response()->json([
                'status' => 1,
                'message' => trans('api-message.VIDEO_DETAILS_GET_SUCCESSFULLY'),
                'data' => new VideoSeriresAuthorVideoResource($video)
            ]);
        } catch(\Exception $e){
            DB::rollback();
            Log::error(strtr(trans('log-messages.DEFAULT_ERROR_MESSAGE'), [
                '<Message>' => $e->getMessage(),
            ]));
            return response()->json([
                'status' => 0,
                'message' => trans('api-message.DEFAULT_ERROR_MESSAGE'),
            ], 500);
        }
    }

    /**
     * Add Video
     *
     * @param Request $request
     * @param $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function addVideo(Request $request, $id){
        try {
            $rule = [
                'title' => 'required',
                'type' => Rule::in([Config::get('constant.SERIES_VIDEO'), Config::get('constant.INTRO_VIDEO')])
            ];

            if (isset($request->id) && !empty($request->id)){
                $rule['size'] = 'integer|min:1';
                $rule['ext'] = 'in:'.implode(',',Config::get('constant.EXTENSION'));
            } else{
                $rule['size'] = 'required|integer|min:1';
                $rule['ext'] = 'required|in:'.implode(',',Config::get('constant.EXTENSION'));
            }

            $message = [
                'ext.in' => trans('api-message.INVALID_FILE')
            ];

            $validator = Validator::make($request->all(), $rule, $message);

            if ($validator->fails()) {
                return response()->json([
                    'status' => 0,
                    'message' => $validator->messages()->all()[0],
                ]);
            }

            $videoBook = VideoBook::find($id);

            if (!$videoBook){
                return response()->json([
                    'status' => 0,
                    'message' => trans('api-message.VIDEO_BOOK_NOT_FOUND')
                ]);
            }

            if ($request->type == Config::get('constant.INTRO_VIDEO')){
                $introVideoCount = $videoBook->introVideos()->count();

                if ($introVideoCount >= 1){
                    return response()->json([
                        'status' => 0,
                        'message' => trans('api-message.INTRO_VIDEO_ALREADY_UPLOADED')
                    ]);
                }
            }

            if (isset($request->video_id) && !empty($request->video_id)){
                $video = VideoBookVideo::find($request->id);
                $video->title = $request->title;
                $video->descr = $request->descr;

                $video->save();
                $message = trans('api-message.VIDEO_UPDATE_SUCCESSFULLY');
            }else{
                $approvedVideoCount = $videoBook->videos()->where('is_approved', 1)->count();
                if ($approvedVideoCount >= Config::get('constant.MAX_VIDEOS_PER_BOOK')){
                    return response()->json([
                        'status' => 0,
                        'message' => trans('api-message.MAX_LIMIT_ERROR'),
                    ]);
                }
                $unApprovedVideoCount = $videoBook->videos()->where('is_approved', 0)->count();
                if ($unApprovedVideoCount >= 1){
                    return response()->json([
                        'status' => 0,
                        'message' => trans('api-message.APPROVED_ONE_ERROR'),
                    ]);
                }
                $video = new VideoBookVideo();
                $video->e_video_book_id = $videoBook->id;
                $video->title = $request->title;
                $video->descr = $request->descr;
                $video->path = FileService::generateFileName($request->ext);
                $video->size = $request->size;
                $video->type = $request->type;
                $video->resumed_at = (string) Carbon::now();
                $video->status = Config::get('constant.ACTIVE_FLAG');

                if ($video->type == Config::get('constant.INTRO_VIDEO')){
                    $video->is_approved = 1;
                    $video->approved_by = auth()->user()->id;
                    $video->approved_at = Carbon::now();
                }

                $video->save();

                $message = trans('api-message.VIDEO_ADD_SUCCESSFULLY');
            }

            return response()->json([
                'status' => 1,
                'message' => $message,
                'data' => new VideoSeriresAuthorVideoResource($video),
            ]);
        } catch(\Exception $e){
            DB::rollback();
            Log::error(strtr(trans('log-messages.DEFAULT_ERROR_MESSAGE'), [
                '<Message>' => $e->getMessage(),
            ]));
            return response()->json([
                'status' => 0,
                'message' => trans('api-message.DEFAULT_ERROR_MESSAGE'),
            ], 500);
        }
    }

    /**
     * Upload Video in chunk
     *
     * @param Request $request
     * @param $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function uploadChunkVideo(Request $request)
    {
        try {
            $video_id = $request->video_id;

            $start = $request->start;
            $end = $request->end;

            DB::beginTransaction();
            $video = VideoBookVideo::find($video_id);
            if (!$video){
                return response()->json([
                    'status' => 0,
                    'message' => trans('api-message.VIDEO_NOT_FOUND'),
                ]);

            }

            $address = Storage::disk('public')->path($this->videoTempPath.$video->path);
            $size=0;
            if(file_exists($address)){
                $size=filesize($address);
            }

            if ($size == 0){
                $progressPercent = "0";
            } else{
                $progressPercent = ($size/($video->size))*100;
            }

            if ($start < $size){
                return response()->json([
                    'status' => 0,
                    'message' => trans('api-message.ALREADY_UPLOAD_CHUNK'),
                ]);
            }

            if ($end == $size){
                return response()->json([
                    'status' => 0,
                    'message' => trans('api-message.ALREADY_UPLOAD_CHUNK'),
                ]);
            }

            $message = trans('api-message.VIDEO_UPLOAD_IS_PROGRESS');
            $video->chunkPointer = $size;
            $video->progressPercent = number_format($progressPercent,2,'.','');
            $data = new VideoSeriresAuthorVideoResource($video);
            unset($video->chunkPointer);
            unset($video->progressPercent);



            $uploadManager = new \UploadManager\Upload('data');
            //add validations
            /*$uploadManager->addValidations([
                new \UploadManager\Validations\Size('2048M'), //maximum file size must be 2M
                new \UploadManager\Validations\Extension(['jpg', 'jpeg', 'png', 'gif', 'mp4', 'm4p', 'm4v']),
            ]);*/

            //add callback : remove uploaded chunks on error
            $uploadManager->afterValidate(function ($chunk) {
                $address = ($chunk->getSavePath() . $chunk->getNameWithExtension());
                if ($chunk->hasError() && file_exists($address)) {
                    //remove current chunk on error
                    @unlink($address);
                }
            });

            //add callback : update database's log
            $uploadManager->afterUpload(function ($chunk) use ($video, $end, &$data) {
                $completed = ($video->size == $chunk->getSavedSize());

                if ($completed) {
                    $videoHelper = new VideoService();

                    $params = [
                        'fileName' => $video->path,
                        'originalPath' => Config::get('constant.SERIES_VIDEO_TEMP_UPLOAD_PATH'),
                        'imageOriginalPath' => Config::get('constant.SERIES_VIDEO_THUMB_UPLOAD_PATH'),
                    ];

                    $response = $videoHelper->setChunkVideo($params);

                    if ($response['status'] == 1) {

                        $info = $videoHelper->getInfo();

                        if (isset($info['status']) && $info['status'] == 0) {
                            return [
                                'status' => $info['status'],
                                'message' => $info['message'],
                            ];
                        }

                        $video->lengh = $info['duration'];
                        $video->thumb_path = basename($info['thumb']);

                        // series thumb
                        $videoBook = VideoBook::find($video->e_video_book_id);
                        if (empty($videoBook->image)){
                            $videoBook->image = basename($info['thumb']);
                            // Copy thumb image to s3

                            // thumb to move on S3
                            $file = file_get_contents(Storage::disk('s3')->url($info['thumb']));


                            Log::info('File Move Start on S3');
                            $moveToS3 = Storage::disk('s3')->put($this->seriesThumbPath . $videoBook->image, (string) $file, 'public');

                            Log::info('File Move Successfully on S3');

                            $videoBook->save();
                        }

                        $videoInfo = array(
                            'id' => $video->id,
                            'file_name' => $info['filename'],
                            'video_url' => $info['file'],
                            'video_name' => basename($info['file']),
                            'thumb_url' => $info['thumb'],
                            'thumb_name' => basename($info['thumb']),
                            'file_size' => $info['filesize'],
                            'file_type' => $info['fileextension'],
                            'duration' => $info['duration'],
                            'current_width' => $info['width'],
                            'current_height' => $info['height'],
                            'bit_rate' => $info['bit_rate'],
                            'frame_rate' => $info['r_frame_rate'],
                            'rotation' => $info['rotation'],
                            'codecid' => $info['codecid'],
                            'codec_name' => $info['codec_name'],
                            'level' => $info['level'],
                            'profile' => $info['profile'],
                            'is_transcoded' => Config::get('constant.TRANSCODING_PENDING_VIDEO_STATUS'),
                        );

                        // Dispatch job to process the video
                        $this->dispatch(new TranscodeVideo($videoInfo));
                    }
                    Log::info('File Upload and Thumb creation complete');
                    $message = trans('api-message.VIDEO_UPLOAD_SUCCESSFULLY');

                }

                $video->resumed_at = ($completed == true ? null : Carbon::now());
                $video->completed_at = ($completed == true ? Carbon::now() : null);
                $video->save();

                DB::commit();

                $video->chunkPointer = intval($chunk->getSavedSize());
                $video->progressPercent = number_format(($end / ($video->size)) * 100, 2, '.','');
                $data = new VideoSeriresAuthorVideoResource($video);
            });

            $chunks = $uploadManager->upload(
                Storage::disk('public')->path($this->videoTempPath),
                $video->path,
                false,
                $start,
                $end - $start + 1
            );

            return response()->json([
                'status' => 1,
                'message' => $message,
                'data' => $data
            ], 200);

        } catch (\Exception $e) {
            DB::rollback();
            Log::error(strtr(trans('log-messages.DEFAULT_ERROR_MESSAGE'), [
                '<Message>' => $e->getMessage(),
            ]));
            return response()->json([
                'status' => 0,
                'message' => trans('api-message.DEFAULT_ERROR_MESSAGE'),
            ], 500);
        } catch (\UploadManager\Exceptions\Upload $exception) {
            //send bad request error
            if (!empty($exception->getChunk())) {
                $chunk = $exception->getChunk();
                DB::rollback();
                Log::error(strtr(trans('log-messages.DEFAULT_ERROR_MESSAGE'), [
                    '<Message>' => $exception->getMessage(),
                ]));
                return response()->json([
                    'status' => 0,
                    'message' => trans('api-message.DEFAULT_ERROR_MESSAGE'),
                ], 500);
            } else {
                DB::rollback();
                Log::error(strtr(trans('log-messages.DEFAULT_ERROR_MESSAGE'), [
                    '<Message>' => $exception->getMessage(),
                ]));
                return response()->json([
                    'status' => 0,
                    'message' => trans('api-message.DEFAULT_ERROR_MESSAGE'),
                ], 500);
            }
        }
    }
}
