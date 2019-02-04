<?php

namespace App\Http\Controllers\Api;

use App\Helpers\Helpers;
use App\Http\Controllers\Controller;
use App\Http\Resources\VideoBookDetailResource;
use App\Http\Resources\VideoBookResource;
use App\Http\Resources\VideoBookVideoResource;
use App\Http\Resources\VideoSeriesVideoResource;
use App\Http\Resources\VideoSeriresAuthorVideoResource;
use App\Models\VideoBook;
use App\Models\VideoCategory;
use App\Models\VideoWatchHistory;
use App\Models\Setting;
use App\Jobs\SaveUserHistory;
use App\Models\User;
use App\Services\ImageUpload;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Response;
use JWTAuth;

class VideoBookController extends Controller
{

    public function __construct()
    {
        $this->videoSeriesOriginalImagePath = Config::get('constant.VIDEO_SERIES_ORIGINAL_PHOTO_UPLOAD_PATH');
        $this->videoSeriesThumbImagePath = Config::get('constant.VIDEO_SERIES_THUMB_PHOTO_UPLOAD_PATH');
        $this->videoSeriesThumbImageHeight = Config::get('constant.VIDEO_SERIES_THUMB_PHOTO_HEIGHT');
        $this->videoSeriesThumbImageWidth = Config::get('constant.VIDEO_SERIES_THUMB_PHOTO_WIDTH');
        $this->videoTempPath = Config::get('constant.SERIES_VIDEO_TEMP_UPLOAD_PATH');
        $this->videoPath = Config::get('constant.SERIES_VIDEO_UPLOAD_PATH');

    }
    /**
     * Get video series detail
     *
     * @param Request $request
     * @return json response from server
     * @throws Exception If there was a any exeption error
     * @Get("video-series/{id}")
     */
    public function getVideoSeriesDetail(Request $request, $id)
    {
        try {

            // Get current user id
            $user = $request->user();
            $userId = $user->id;

            // Get video series
            $videoSeries = VideoBook::withCount(['users' => function ($query) use ($userId) {
                $query->where('users.id', $userId);
            }])->find($id);
            //$videoSeries = VideoBook::find($id);

            if (empty($videoSeries)) {
                return response()->json([
                    'status' => 0,
                    'message' => trans('api-message.VIDEO_BOOK_NOT_FOUND'),
                ]);
            }

            //dd(empty($videoSeries->deleted_at));


            if ($videoSeries->users_count <= 0){
                if (!empty($videoSeries->deleted_at)) {
                    return response()->json([
                        'status' => 0,
                        'message' => trans('api-message.VIDEO_BOOK_NOT_FOUND'),
                    ]);
                }
            }
            // Get video series details
            $videoSeriesDetail = VideoBook::with('author')
                ->withCount(['users' => function ($query) use ($userId) {
                    $query->where('users.id', $userId);
                }])
                /*->with(['videos' => function($query){
                    $query->where('is_approved',Config::get('constant.APPROVED_VIDEO_FLAG'))
                        ->where('status',Config::get('constant.ACTIVE_FLAG'));
                }])
                ->with('videos.videoLikesByUser', 'videos.videoUnlikesByUser')
                ->with('videos.comments')
                ->with(['videos.userVideoHistory' => function ($query) use ($userId){
                    $query->where('users.id',$userId);
                }])->with(['videos.videoWatchedByUser'=> function ($query) use ($userId){
                    $query->where('users.id',$userId);
                }])*/->with(['introVideos' => function($query){
                    $query->where('is_approved',Config::get('constant.APPROVED_VIDEO_FLAG'))
                        ->where('status',Config::get('constant.ACTIVE_FLAG'));
                }])
                ->where('e_video_book.id', $id)
                ->first();



            $videos = $videoSeries->videos()->with('videoLikesByUser', 'videoUnlikesByUser')
                ->with('comments')
                ->with(['userVideoHistory' => function ($query) use ($userId){
                    $query->where('users.id',$userId);
                }])->with(['videoWatchedByUser'=> function ($query) use ($userId) {
                    $query->where('users.id', $userId);
                }])->where('e_video_book_videos.status',Config::get('constant.ACTIVE_FLAG'));
            if ($userId == $videoSeries->user_id){
                $videos = $videos->get();
                $videos = $videos->each(function ($item){
                    if (boolval($item->completed_at) !== true){
                        $address = Storage::disk('public')->path($this->videoTempPath.$item->path);
                        $size=0;
                        if(file_exists($address)){
                            $size=filesize($address);
                        }

                        if ($size == 0){
                            $progressPercent = "0";
                        } else{
                            $progressPercent = ($size/($item->size))*100;
                        }
                        $item->chunkPointer = $size;
                        $item->progressPercent = number_format($progressPercent,2,'.','');;
                    }
                });
                $videos = VideoSeriresAuthorVideoResource::collection($videos);

                if (!empty($videoSeriesDetail->introVideos->toArray())){
                    $introVideo = $videoSeriesDetail->introVideos[0];

                    if (boolval($introVideo->completed_at) !== true){
                        $address = Storage::disk('public')->path($this->videoTempPath.$introVideo->path);
                        $size=0;
                        if(file_exists($address)){
                            $size=filesize($address);
                        }

                        if ($size == 0){
                            $progressPercent = "0";
                        } else{
                            $progressPercent = ($size/($introVideo->size))*100;
                        }
                        $introVideo->chunkPointer = $size;
                        $introVideo->progressPercent = number_format($progressPercent,2,'.','');;
                    }
                    // check file
                    if ($videoSeriesDetail->introVideos[0]->is_transacoded == Config::get('constant.TRANSCODING_DONE_VIDEO_STATUS')){
                        $object_key = $this->videoPath.$videoSeriesDetail->introVideos[0]->path;
                    } elseif ($videoSeriesDetail->introVideos[0]->is_transacoded == Config::get('constant.TRANSCODING_FAILED_VIDEO_STATUS')) {
                        $object_key = $this->videoTempPath.$videoSeriesDetail->introVideos[0]->path;
                    }
                    else {
                        $object_key = '';
                    }
                    isset($object_key) && !empty($object_key) ? $introVideo->videoSignedUrl = Helpers::generateAWSSignedUrl($object_key) : $introVideo->videoSignedUrl = null ;

                    $introVideo = new VideoSeriresAuthorVideoResource($introVideo);
                } else{
                    $introVideo = new \stdClass();
                }
            }else{
                $videos = $videos->where('is_approved',Config::get('constant.APPROVED_VIDEO_FLAG'));
                $videos = $videos->orderBy('created_at', 'desc')->get();
                $videos = VideoSeriesVideoResource::collection($videos);


                // Intro Video
                if (!empty($videoSeriesDetail->introVideos->toArray())){
                    $introVideo = $videoSeriesDetail->introVideos[0];
                    // check file
                    if ($introVideo->is_transacoded == Config::get('constant.TRANSCODING_DONE_VIDEO_STATUS')){
                        $object_key = $this->videoPath.$videoSeriesDetail->introVideos[0]->path;
                    } elseif ($introVideo->is_transacoded == Config::get('constant.TRANSCODING_FAILED_VIDEO_STATUS')) {
                        $object_key = $this->videoTempPath.$videoSeriesDetail->introVideos[0]->path;
                    }
                    else {
                        $object_key = null;
                    }
                    isset($object_key) && !empty($object_key) ? $introVideo->videoSignedUrl = Helpers::generateAWSSignedUrl($object_key) : $introVideo->videoSignedUrl = null ;

                    $introVideo = new VideoSeriesVideoResource($introVideo);
                } else{
                    $introVideo = new \stdClass();
                }
            }


            SaveUserHistory::dispatch($userId,$id);

            return response()->json([
                'status' => 1,
                'message' => trans('api-message.DEFAULT_SUCCESS_MESSAGE'),
                'data' =>[
                    'seriesDetail' => new VideoBookDetailResource($videoSeriesDetail),
                    'videos' => $videos,
                    'introVideo' => $introVideo
                ]
            ]);
        } catch (\Exception $e) {
            // Log Message
            Log::error(strtr(trans('log-messages.DEFAULT_ERROR_MESSAGE'), [
                '<Message>' => $e->getMessage(),
            ]));
            return response()->json([
                'status' => 0,
                'message' => trans('api-message.DEFAULT_ERROR_MESSAGE'),
            ]);
        }
    }

    /**
     * Get my series List
     *
     * @param Request $request
     * @return json response from server
     * @throws Exception If there was a any exeption error
     * @Get("user/me/series?page=1")
     */
    public function getSeriesList(Request $request)
    {
        try{
            // Rule Validation - Start
            $rule = [
                'page' => 'required|integer|min:1',
            ];

            $validator = Validator::make($request->all(), $rule);

            if ($validator->fails()) {
                return response()->json([
                    'status' => 0,
                    'message' => $validator->messages()->all()[0],
                ]);
            }
            // Rule Validation - Ends

            // Get User
            $user = $request->user();

            // Get active category videos list
            $getSeries = VideoBook::where('user_id',$user->id)
                ->withCount('users')
                ->whereHas('author', function ($query) {
                    $query->whereNull('deleted_at');
                })->orderBy('created_at', 'desc')
                // ->where('status', Config::get('constant.ACTIVE_FLAG'))
                ->paginate(Config::get('constant.SERIES_LIST_PER_PAGE'));

            // Get response success
            return response()->json([
                'status' => 1,
                'message' => trans('api-message.SERIES_LIST_FETCHED_SUCCESSFULLY'),
                'total_count' => $getSeries->total(),
                'next' => $getSeries->hasMorePages(),
                'previous' => $getSeries->onFirstPage() ? false : true,
                'data' => VideoBookDetailResource::collection($getSeries),
            ]);

        } catch(\Exception $e){
            // Log Message
            Log::error(strtr(trans('log-messages.DEFAULT_ERROR_MESSAGE'), [
                '<Message>' => $e->getMessage(),
            ]));
            return response()->json([
                'status' => 0,
                'message' => trans('api-message.DEFAULT_ERROR_MESSAGE'),
            ]);
        }
    }

    /**
     * Get Purchased  Series List
     *
     * @param Request $request
     * @return json response from server
     * @throws Exception If there was a any exeption error
     * @Get("user/me/series/purchased?page=1")
     */

    public function getPurchasedSeriesList(Request $request)
    {
        try{
            // Rule Validation - Start
            $rule = [
                'page' => 'required|integer|min:1',
            ];

            $validator = Validator::make($request->all(), $rule);

            if ($validator->fails()) {
                return response()->json([
                    'status' => 0,
                    'message' => $validator->messages()->all()[0],
                ]);
            }
            // Rule Validation - Ends

            // Get User
            $user = $request->user();

            $userId = $request->user()->id;

            // Get Purchased series list
            $getSeries = $user->userPurchasedSeries()->withCount('users')
                ->with(['users' => function ($query) use ($userId) {
                    $query->where('users.id', $userId);
                }])->orderBy('created_at', 'desc')->paginate(Config::get('constant.SERIES_LIST_PER_PAGE'));

            //dd($getSeries[0]->author()->withTrashed()->first());

            // Get response success
            return response()->json([
                'status' => 1,
                'message' => trans('api-message.SERIES_LIST_FETCHED_SUCCESSFULLY'),
                'total_count' => $getSeries->total(),
                'next' => $getSeries->hasMorePages(),
                'previous' => $getSeries->onFirstPage() ? false : true,
                'data' => VideoBookDetailResource::collection($getSeries),
            ]);

        } catch(\Exception $e){
            // Log Message
            Log::error(strtr(trans('log-messages.DEFAULT_ERROR_MESSAGE'), [
                '<Message>' => $e->getMessage(),
            ]));
            return response()->json([
                'status' => 0,
                'message' => trans('api-message.DEFAULT_ERROR_MESSAGE'),
            ]);
        }
    }

    /**
     * Get video series list
     *
     * @param Request $request
     * @return json response from server
     * @throws Exception If there was a any exeption error
     * @Get("video-series/list?page=1&searchBy=&sort=&sortOrder=")
     */
    public function getVideoSeriesList(Request $request)
    {
        try {
            // Rule Validation - Start
            $rule = [
                'page' => 'required|integer|min:1'
            ];

            $validator = Validator::make($request->all(), $rule);

            if ($validator->fails()) {
                return response()->json([
                    'status' => 0,
                    'message' => $validator->messages()->all()[0],
                ]);
            }
            // Rule Validation - Ends

            // Get User
            $user = $request->user();

            // Search
            $searchBy = (isset($request->searchBy) && !empty($request->searchBy)) ? $request->searchBy : null;

            // Sort
            $sort = (isset($request->sortBy) && !empty($request->sortBy)) ? $request->sortBy : 'id';
            $order = (isset($request->sortOrder) && !empty($request->sortOrder)) ? $request->sortOrder : 'ASC';

            // Get video series details
            $getSeries = VideoBook::withCount('users')
                ->whereHas('author', function ($query) {
                    $query->whereNull('deleted_at');
                })->with(['users' => function ($query) use ($user)  {
                $query->where('users.id',$user->id);
            }])->where(function($q) use ($searchBy) {
                    $q->where('title','LIKE','%'.$searchBy.'%')
                        ->orWhere('descr','LIKE','%'.$searchBy.'%')
                        ->orWhere('price','LIKE','%'.$searchBy.'%');
                })->orderBy($sort,$order)
                ->where('status', Config::get('constant.ACTIVE_FLAG'))
                ->orderBy('created_at', 'desc')
                ->paginate(Config::get('constant.SERIES_LIST_PER_PAGE'));

            // Get response success
            return response()->json([
                'status' => 1,
                'message' => trans('api-message.SERIES_LIST_FETCHED_SUCCESSFULLY'),
                'total_count' => $getSeries->total(),
                'next' => $getSeries->hasMorePages(),
                'previous' => $getSeries->onFirstPage() ? false : true,
                'data' => VideoBookDetailResource::collection($getSeries),
            ]);
        } catch (\Exception $e) {
            // Log Message
            Log::error(strtr(trans('log-messages.DEFAULT_ERROR_MESSAGE'), [
                '<Message>' => $e->getMessage(),
            ]));
            return response()->json([
                'status' => 0,
                'message' => trans('api-message.DEFAULT_ERROR_MESSAGE'),
            ]);
        }
    }

    /**
     * Get Now Trending Series List
     *
     * @param Request $request
     * @return json response from server
     * @throws Exception If there was a any exeption error
     * @Get("video-series/trending?page=1")
     */
    public function getTrendingVideoSeriesList(Request $request)
    {
        try{
            // Rule Validation - Start
            $rule = [
                'page' => 'required|integer|min:1',
            ];

            $validator = Validator::make($request->all(), $rule);

            if ($validator->fails()) {
                return response()->json([
                    'status' => 0,
                    'message' => $validator->messages()->all()[0],
                ]);
            }
            // Rule Validation - Ends

            $setting =

                // Get User
            $user = $request->user();

            // Get Trending videos series list
            $getSeries = VideoBook::withCount(['users' => function($query){
                $query->whereBetween('user_purchased_e_video_books.created_at', [date('Y-m-d', strtotime('-15 days')), date('Y-m-d')]);
            }])->whereHas('author', function ($query) {
                $query->whereNull('deleted_at');
            })->whereHas('users')->orderBy('users_count','DESC')
                ->where('status', Config::get('constant.ACTIVE_FLAG'))
                ->orderBy('created_at', 'desc')
                ->paginate(Config::get('constant.SERIES_LIST_PER_PAGE'));

            // All good so retuen response success
            return response()->json([
                'status' => 1,
                'message' => trans('api-message.NOW_TREADING_SERIES_LIST_FETCHED_SUCCESSFULLY'),
                'total_count' => $getSeries->total(),
                'next' => $getSeries->hasMorePages(),
                'previous' => $getSeries->onFirstPage() ? false : true,
                'data' => VideoBookDetailResource::collection($getSeries),
            ]);

        } catch(\Exception $e){
            // Log Message
            Log::error(strtr(trans('log-messages.DEFAULT_ERROR_MESSAGE'), [
                '<Message>' => $e->getMessage(),
            ]));
            return response()->json([
                'status' => 0,
                'message' => trans('api-message.DEFAULT_ERROR_MESSAGE'),
            ]);
        }
    }

    /**
     * Get History
     *
     * @param Request $request
     * @return json response from server
     * @throws Exception If there was a any exeption error
     * @Get("user/me/history?page=1")
     */
    public function getHistory(Request $request)
    {
        try {
            // Rule Validation - Start
            $rule = [
                'page' => 'required|integer|min:1'
            ];

            $validator = Validator::make($request->all(), $rule);

            if ($validator->fails()) {
                return response()->json([
                    'status' => 0,
                    'message' => $validator->messages()->all()[0],
                ]);
            }
            // Rule Validation - Ends

            // Get Series History
            $getSeries = $request->user()->userHistory()->where('e_video_book.status', Config::get('constant.ACTIVE_FLAG'))->paginate(Config::get('constant.SERIES_HISTORY_PER_PAGE'));

            // dd($getSeries);

            // Get response success
            return response()->json([
                'status' => 1,
                'message' => trans('api-message.HISTORY_FETCHED_SUCCESSFULLY'),
                'total_count' => $getSeries->total(),
                'next' => $getSeries->hasMorePages(),
                'previous' => $getSeries->onFirstPage() ? false : true,
                'data' => VideoBookDetailResource::collection($getSeries),
            ]);
        } catch (\Exception $e) {
            // Log Message
            Log::error(strtr(trans('log-messages.DEFAULT_ERROR_MESSAGE'), [
                '<Message>' => $e->getMessage(),
            ]));
            return response()->json([
                'status' => 0,
                'message' => trans('api-message.DEFAULT_ERROR_MESSAGE'),
            ]);
        }
    }

    /**
     * Get Something New series List
     *
     * @param Request $request
     * @return json response from server
     * @throws Exception If there was a any exeption error
     * @Get("video-series/somethingNew?page=1")
     */

    public function getSomethingNewSeriesList(Request $request){
        try{

            // Get Series id from Setting
            $setting = Setting::where('slug',Config::get('constant.SOMETHING_NEW_EVERYDAY'))->pluck('value')->first();

            $settingSeriesid = explode(",",$setting);

            // Get Series Details
            $getSeries = VideoBook::withCount('users')->whereHas('author', function ($query) {
                $query->whereNull('deleted_at');
            })->whereIn('id', $settingSeriesid)->inRandomOrder()->get();

            // All good so retuen response success
            return response()->json([
                'status' => 1,
                'message' => trans('api-message.SOMETHING_NEW_SERIES_LIST_FETCHED_SUCCESSFULLY'),
                'data' => VideoBookDetailResource::collection($getSeries),
            ]);

        } catch(\Exception $e){
            // Log Message
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
     * Add New Video Series
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function addOrUpdateVideoSeries(Request $request){
        try {
            // Rule Validation - Start
            $rule = [
                'video_category_id' => 'required|integer|min:1',
                'title' => 'required|max:100', // |regex:/^(?![0-9]*$)[a-zA-Z0-9 ]+$/
                'descr' => 'required',
            ];


            if (isset($request->id) && !empty($request->id)) {
                $rule['id'] = 'required|integer|min:1';
                $rule['image'] = 'image';
            } else{
                $rule['image'] = 'image';
            }


            $validator = Validator::make($request->all(), $rule);

            if ($validator->fails()) {
                return response()->json([
                    'status' => 0,
                    'message' => $validator->messages()->all()[0],
                ]);
            }

           
            // $videoBookdata = VideoBook::where('user_id',$user->id)->where('status',Config::get('constant.INACTIVE_FLAG'))->exists();

            // if($videoBookdata){
            //     return response()->json([
            //         'status' => 0,
            //         'message' => trans('api-message.FIRST_ACTIVE_ALREADY_CREATED_SERIES'),
            //     ]);
            // }

            $user = $request->user();

            if (empty($user->stripe_account_id) && empty($user->birth_date) && empty($user->bio)){
                return response()->json([
                    'status' => 0,
                    'message' => trans('api-message.INVALID_AUTHOR'),
                ]);
            }

            DB::beginTransaction();
            $postData = $request->only('video_category_id', 'title', 'image','descr');
            $postData['max_video_limit'] = Config::get('constant.MAX_VIDEOS_PER_BOOK');
            $postData['status'] = Config::get('constant.INACTIVE_FLAG');
            $postData['author_profit_in_percentage'] = Config::get('constant.AUTHOR_PROFIT_IN_PERCENTAGE');
            $postData['user_id'] = $user->id;

            if(isset($request->id) && !empty($request->id)){
                $videoBook = VideoBook::find($request->id);
                $path = $videoBook['image'];
            } else{
                $path = '';
            }

            // Upload User Photo
            if (!empty($request->file('image')) && $request->file('image')->isValid()) {
                $params = [
                    'originalPath' => $this->videoSeriesOriginalImagePath,
                    'thumbPath' => $this->videoSeriesThumbImagePath,
                    'thumbHeight' => $this->videoSeriesThumbImageHeight,
                    'thumbWidth' => $this->videoSeriesThumbImageWidth,
                    'previousImage' => $path,
                ];
                $videoSeriesImage = ImageUpload::uploadWithThumbImage($request->file('image'), $params);

                if ($videoSeriesImage === false) {
                    DB::rollback();

                    return response()->json([
                        'status' => 0,
                        'message' => trans('api-message.USER_IMAGE_UPLOAD_ERROR_MSG'),
                    ]);

                } else {
                    $postData['image'] = $videoSeriesImage['imageName'];
                }
            }

            if(isset($request->id) && !empty($request->id)){
                $videoBook = VideoBook::find($request->id);
                $videoBook->update($postData);
                DB::commit();

                return response()->json([
                    'status' => 1,
                    'message' => trans('api-message.UPDATE_VIDEO_BOOK'),
                    'data' =>  new VideoBookResource($videoBook)
                ]);
            }else{
                $videoBook = new VideoBook($postData);
                $videoBook->save();
                DB::commit();

                return response()->json([
                    'status' => 1,
                    'message' => trans('api-message.ADD_VIDEO_BOOK'),
                    'data' => new VideoBookResource($videoBook)
                ]);
            }
        } catch (\Exception $e) {
            // Log Message
            Log::error(strtr(trans('log-messages.DEFAULT_ERROR_MESSAGE'), [
                '<Message>' => $e->getMessage(),
            ]));
            return response()->json([
                'status' => 0,
                'message' => trans('api-message.DEFAULT_ERROR_MESSAGE'),
            ]);
        }
    }
}
