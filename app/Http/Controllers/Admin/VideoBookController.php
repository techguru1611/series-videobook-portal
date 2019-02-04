<?php

namespace App\Http\Controllers\Admin;

use App\Helpers\Helpers;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use App\Jobs\TranscodeVideo;
use App\Models\VideoBook;
use App\Models\VideoBookVideo;
use App\Models\UserPurchasedEVideoBooks;
use App\Models\VideoCategory;
use App\Models\VideoSubCategory;
use App\Services\UrlService;
use App\Services\VideoService;
use App\Services\ImageUpload;
use Auth;
use Carbon\Carbon;
use Config;
use DB;
use Log;
use Redirect;
use Response;
use Storage;
use Validator;

class VideoBookController extends Controller
{
    public function __construct()
    {
        $this->videoSeriesOriginalImagePath = Config::get('constant.VIDEO_SERIES_ORIGINAL_PHOTO_UPLOAD_PATH');
        $this->videoSeriesThumbImagePath = Config::get('constant.VIDEO_SERIES_THUMB_PHOTO_UPLOAD_PATH');
        $this->videoSeriesThumbImageHeight = Config::get('constant.VIDEO_SERIES_THUMB_PHOTO_HEIGHT');
        $this->videoSeriesThumbImageWidth = Config::get('constant.VIDEO_SERIES_THUMB_PHOTO_WIDTH');
        $this->videosThumbPath = Config::get('constant.SERIES_VIDEO_THUMB_UPLOAD_PATH');
    }
    /**
     * [index - Display a listing of the resource.]
     *
     * @return [Illuminate\Support\Facades\View]
     */
    public function index()
    {
        return view('admin.video-books.list');
    }

    /**
     * [listAjax - List VideoBook]
     *
     * @param  [\Illuminate\Http\Request]   [$request]
     * @return [json]                       [list of VideoBook]
     */
    public function listAjax(Request $request)
    {
        $records = array();
        if ($request->customActionType == 'groupAction') {

            $action = $request->customActionName;
            $idArray = $request->id;

            if ($action == 'delete') {
                foreach ($idArray as $_idArray) {
                    $_idArray = UrlService::base64UrlDecode($_idArray);
                    $this->deleteVideoBook($_idArray);
                }
                $records["message"] = trans('admin-message.VIDEO_BOOK_DELETED_SUCCESSFULLY_MESSAGE');
            }
            if ($action == 'status') {
                foreach ($idArray as $_idArray) {
                    $_idArray = UrlService::base64UrlDecode($_idArray);
                    $videoBook = VideoBook::find($_idArray);
                    $introVideo = $videoBook->introVideos();
                    $videoCount = $videoBook->videos()->where('is_approved', Config::get('constant.APPROVED_VIDEO_FLAG'))->where('status', Config::get('constant.ACTIVE_FLAG'))->count();
                    if ($videoBook->price == 0.00){
                        $records["err"] = trans('admin-message.PLEASE_ADD_PRICE');
                    } elseif ($videoCount < 1 && empty($introVideo)){
                        $records["err"] = trans('admin-message.ADD_ATLEAST_ONE_VIDEO_AND_INTROVIDEO');
                    }else{
                        VideoBook::updateVideoBookStatus($_idArray);
                        $records["message"] = trans('admin-message.VIDEO_BOOK_STATUS_UPDATED_SUCCESS_MESSAGE');
                    }
                }

            }
        }

        $columns = array(
            0 => 'video_category_title',
            1 => 'full_name',
            2 => 'title',
            3 => 'image',
            4 => 'descr',
            5 => 'price',
            6 => 'total_download',
            7 => 'author_profit_in_percentage',
            8 => 'status',
        );
        $order = $request->order;
        $search = $request->search;
        $records["data"] = array();

        // Getting records from the VideoBook table
        $iTotalRecords = VideoBook::getVideoBookCount();
        $iTotalFiltered = $iTotalRecords;
        $iDisplayLength = intval($request->length) <= 0 ? $iTotalRecords : intval($request->length);
        $iDisplayStart = intval($request->start);
        $sEcho = intval($request->draw);

        $records["data"] = VideoBook::leftJoin('video_category', 'video_category.id', '=', 'e_video_book.video_category_id')
            ->leftJoin('users', 'users.id', '=', 'e_video_book.user_id')
            ->where('e_video_book.status', '<>', Config::get('constant.DELETED_FLAG'));

        if (!empty($search['value'])) {
            $val = $search['value'];
            $records["data"]->where(function ($query) use ($val) {
                $query->SearchVideoCategoryTitle($val)
                    ->SearchVideoAuthor($val)
                    ->SearchVideoBookTitle($val)
                    ->SearchVideoBookDescription($val)
                    ->SearchVideoBookPrice($val)
                    ->SearchVideoBookTotalDownload($val)
                    ->SearchVideoBookAuthorProfit($val)
                    ->SearchVideoBookStatus($val);
            });

            // No of record after filtering
            $iTotalFiltered = $records["data"]->where(function ($query) use ($val) {
                $query->SearchVideoCategoryTitle($val)
                    ->SearchVideoAuthor($val)
                    ->SearchVideoBookTitle($val)
                    ->SearchVideoBookDescription($val)
                    ->SearchVideoBookPrice($val)
                    ->SearchVideoBookTotalDownload($val)
                    ->SearchVideoBookAuthorProfit($val)
                    ->SearchVideoBookStatus($val);
            })->count();
        }

        //order by
        foreach ($order as $o) {
            $records["data"] = $records["data"]->orderBy($columns[$o['column']], $o['dir']);
        }

        // Get Record
        $records["data"] = $records["data"]->take($iDisplayLength)->offset($iDisplayStart)->get([
            'e_video_book.id',
            'e_video_book.title',
            'e_video_book.descr',
            'e_video_book.image',
            'e_video_book.price',
            'e_video_book.total_download',
            'e_video_book.author_profit_in_percentage',
            'e_video_book.status',
            'video_category.title AS video_category_title',
            'users.full_name',
        ]);

        if (!empty($records["data"])) {
            foreach ($records["data"] as $key => $_records) {
                $id = UrlService::base64UrlEncode($_records->id);
                $edit = route('video-books.edit', $id);
                if ($_records->status == "active") {
                    $records["data"][$key]->status = '<a href="javascript:;" title="Make Inactive" class="btn-status-video-books" data-id="' . $id . '"> ' . $_records->status . '</a>';
                } else {
                    $records["data"][$key]->status = '<a href="javascript:;" title="Make Active" class="btn-status-video-books" data-id="' . $id . '"> ' . $_records->status . '</a>';
                }
                $totalDownload = UserPurchasedEVideoBooks::where('e_video_book_id',$_records->id)->count();
                $records["data"][$key]['total_download'] = $totalDownload;
                $descr = strlen($_records->descr) > 50 ? substr($_records->descr,0,50)."..." : $_records->descr;
                $records["data"][$key]['descr'] = $descr;
                $image = !empty($_records->image) ? Storage::disk(env('FILESYSTEM_DRIVER'))->url($this->videoSeriesThumbImagePath . $_records->image)  : asset('images/default_user_profile.png');
                $records["data"][$key]['image'] = '<img src ='.$image.' height=50px width=50px>';
                $records["data"][$key]['action'] = "&emsp;<a href='{$edit}' title='Edit Video Book' ><span class='menu-icon icon-pencil'></span></a>
                                                    &emsp;<a href='javascript:;' data-id='" . $id . "' class='btn-delete-video-books' title='Delete Video Book' ><span class='menu-icon icon-trash'></span></a>";
            }
        }
        $records["draw"] = $sEcho;
        $records["recordsTotal"] = $iTotalRecords;
        $records["recordsFiltered"] = $iTotalFiltered;
        return Response::json($records);
    }

    /**
     * [create - Create VideoBook]
     *
     * @return [Illuminate\Support\Facades\View]    [Create Video book view]
     */
    public function create()
    {
        // Get Active Video category
        $videoCategory = VideoCategory::getActiveVideoCategory();

        $videoSubCategory = [];
        return $this->_update($videoCategory, $videoSubCategory);
    }

    /**
     * [update - Update VideoBook]
     *
     * @param  [Integer]                            [$id - VideoBook id]
     * @return [Illuminate\Support\Facades\View]    [Update Video book view]
     */
    public function update($id)
    {
        $id = UrlService::base64UrlDecode($id);
        $videoBook = VideoBook::with(['videos' => function ($query) {
            $query->where('e_video_book_videos.type', Config::get('constant.INTRO_VIDEO'))
                ->whereNull('e_video_book_videos.deleted_at');
        }])
        ->where('e_video_book.id', $id)
        ->first();
        if ($videoBook === null) {
            return Redirect::to("/admin/video-books")->with('error', trans('admin-message.VIDEO_BOOK_NOT_EXIST'));
        }
        // Get Video category
        $videoCategory = VideoCategory::getVideoCategory();

        return $this->_update($videoCategory, $videoBook);
    }

    /**
     * [_update - Create/Update VideoBook]
     *
     * @param [Object] [$videoCategory - VideoCategory object]
     * @param [Object] [$videoBook - VideoBook object]
     * @return [Illuminate\Support\Facades\View]    [Create/Update Video book view]
     */
    private function _update($videoCategory, $videoBook = null)
    {
        if ($videoBook == null) {
            $videoBook = new VideoBook();
        }

        // Get intro video path if exists
        $introVideoPath = (isset($videoBook->videos[0]) && $videoBook->videos[0]->is_transacoded == Config::get('constant.TRANSCODING_DONE_VIDEO_STATUS')) ? Config::get('constant.SERIES_VIDEO_UPLOAD_PATH') : Config::get('constant.SERIES_VIDEO_TEMP_UPLOAD_PATH');

        $status = Config::get('constant.STATUS');

        return view('admin.video-books.add', compact('videoCategory', 'videoBook', 'status', 'introVideoPath'));
    }

    /**
     * [set - Store a resource in storage.]
     *
     * @param  [\Illuminate\Http\Request]  [$request]
     * @return [\Illuminate\Http\Response] []
     */
    public function set(Request $request)
    {

        try {

            // Decrypt Id
            $id = UrlService::base64UrlDecode($request->id);
            // Rule Validation - Start
            $rule = [
                'video_category_id' => 'required|integer|min:1',
                'title' => 'required|max:100', // |regex:/^(?![0-9]*$)[a-zA-Z0-9 ]+$/
                'descr' => 'required',
                'price' => 'required|regex:/^\s*(?=.*[1-9])\d*(?:\.\d{1,2})?\s*$/', // Amount with 2 decimal
                'image' => 'image',
                'author_profit_in_percentage' => 'required|regex:/[0-9]?[0-9]?[0-9]?(\.[0-9][0-9]?)?$/', // Not greater than 100 with decimal
                'introVideo' => 'nullable|mimetypes:video/mpeg,video/mp4,video/avi,video/x-ms-wmv,video/quicktime,video/x-msvideo,video/webm,video/ogg,video/x-flv,video/3gpp',
            ];

            if ($id == 0 || empty($id)){
                $rule['image'] = 'required|image';
            }


            if (isset($request->id) && UrlService::base64UrlDecode($request->id) > 0) {
                $rule['status'] = ['required', Rule::in([Config::get('constant.ACTIVE_FLAG'), Config::get('constant.INACTIVE_FLAG')])];
            }

            $validator = Validator::make($request->all(), $rule);

            if ($validator->fails()) {
                return Redirect::back()->withInput($request->all())->withErrors($validator);
            }

            // Rule Validation - End

            // Decrypt Id
            $id = UrlService::base64UrlDecode($request->id);

            // Get VideoBook
            $videoBook = VideoBook::find($id);
            $path = $videoBook['image'];

            $postData = $request->only('video_category_id', 'title', 'image','descr', 'price', 'author_profit_in_percentage');
            $postData['approved_by'] = Auth::user()->id;
            $postData['price_by_author'] = $postData['price'];

            // Start database transaction
            DB::beginTransaction();

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

                    // Log the error
                    Log::error(strtr(trans('log-messages.VIDEO_CATEGORY_UPLOAD_ERROR_MESSAGE'), [
                        '<Message>' => '',
                    ]));

                    return Redirect::back()->withInput($request->all())->with('error', trans('admin-message.VIDEO_BOOK_IMAGE_UPLOAD_ERROR_MSG'));
                } else {
                    $postData['image'] = $videoSeriesImage['imageName'];
                }
            }
            if (isset($request->id) && UrlService::base64UrlDecode($request->id) > 0) {

                // Check if any video and intro video uploaded to make this active
                if ($request->status == Config::get('constant.ACTIVE_FLAG')) {
                    $approvedVideoCount = $videoBook->videos()->where('is_approved', Config::get('constant.APPROVED_VIDEO_FLAG'))->count();

                    // Has minimum one approved video
                    if ($approvedVideoCount == 0) {
                        DB::rollback();
                        Log::error(strtr(trans('log-messages.UPLOAD_ATLEAST_ONE_VIDEO_TO_MAKE_VIDEO_BOOK_ACTIVE'), [
                            '<Video Book Id>' => UrlService::base64UrlDecode($request->id),
                        ]));
                        return Redirect::back()->withInput($request->all())->with('error', trans('admin-message.UPLOAD_ATLEAST_ONE_VIDEO_TO_MAKE_VIDEO_BOOK_ACTIVE'));
                    }
                }
                $postData['status'] = $request->status;
                $videoBook->update($postData);
                DB::commit();

                // Upload intro video
                if ($request->hasFile('introVideo')) {
                    if($videoBook->introVideos()->count()){
                        return Redirect::back()->withInput($request->all())->with('error', trans('api-message.INTRO_VIDEO_ALREADY_UPLOADED'));
                    };
                    $introVideoResponse = $this->uploadIntroVideo($request);

                    // Return error if error
                    if ($introVideoResponse['status'] == 0) {
                        DB::rollback();
                        return Redirect::back()->withInput($request->all())->with('error', $introVideoResponse['message']);
                    }

                    // Dispatch job to process the video
                    $this->dispatch(new TranscodeVideo($introVideoResponse));
                }

                return Redirect::to("admin/video-books")->with('success', trans('admin-message.VIDEO_BOOK_UPDATED_SUCCESSFULLY_MESSAGE'));
            } else {
                $postData['user_id'] = Auth::user()->id;
                $postData['status'] = Config::get('constant.INACTIVE_FLAG');
                $postData['max_video_limit'] = Config::get('constant.MAX_VIDEOS_PER_BOOK');
                
                $videoBook = new VideoBook($postData);
                $videoBook->save();
                DB::commit();

                $videoId = UrlService::base64UrlEncode($videoBook->id);
                return Redirect::to("admin/video-books/video-books-{$videoId}")->with('success', trans('admin-message.VIDEO_BOOK_CREATED_SUCCESSFULLY_MESSAGE'));
            }
        } catch (\Exception $e) {
            DB::rollback();
            Log::error(strtr(trans('log-messages.VIDEO_BOOK_ADD_EDIT_ERROR_MESSAGE'), [
                '<Message>' => $e->getMessage(),
            ]));
            return Redirect::to("admin/video-books")->with('error', trans('admin-message.DEFAULT_ERROR_MESSAGE'));
        }
    }

    /**
     * [deleteVideoBook - Delete VideoBook]
     *
     * @param  [Integer] [$id - Remove the specified resource from storage.]
     * @return [Boolean]
     */
    public function deleteVideoBook($id)
    {
        try {
            $videoBook = VideoBook::findOrFail($id);

            // If user not found
            if ($videoBook === null) {
                Log::error(strtr(trans('log-messages.VIDEO_BOOK_DELETE_ERROR_MESSAGE'), [
                    '<Message>' => trans('admin-message.VIDEO_BOOK_YOU_WANT_TO_DELETE_IS_NOT_FOUND'),
                ]));
                return 0;
            }
            $videoBook->delete();
            return 1;
        } catch (\Exception $e) {
            Log::error(strtr(trans('log-messages.VIDEO_BOOK_DELETE_ERROR_MESSAGE'), [
                '<Message>' => $e->getMessage(),
            ]));
            return 0;
        }
    }

    /**
     * [getVideoSubCategoryAjax - Get video sub category list from category id]
     *
     * @param  [\Illuminate\Http\Request]  [$request]
     * @return [json]       [list of video sub category]
     */
    public function getVideoSubCategoryAjax(Request $request)
    {
        try {
            $videoSubCategory = VideoSubCategory::getVideoSubCategoryFromCategoryId($request->videoCategoryId);
            return Response::json($videoSubCategory);
        } catch (\Exception $e) {
            Log::error(strtr(trans('log-messages.VIDEO_BOOK_VIDEO_SUB_CATEGORY_AJAX_ERROR_MESSAGE'), [
                '<Message>' => $e->getMessage(),
            ]));
            return [];
        }
    }

    /**
     * [uploadVideoAjax - Upload video in video book]
     *
     * @param  [\Illuminate\Http\Request]  [$request]
     * @return [json]       [Video info]
     */
    public function uploadVideoAjax(Request $request)
    {
        try {
            // Decrypt Id
            $id = UrlService::base64UrlDecode($request->videoBookId);

            $videoBook = VideoBook::find($id);

            // If video book not found
            if ($videoBook === null) {
                Log::error("Video Book not found while uploading videos. Video Book Id: {$id}");
                return Response::json([
                    'status' => 0,
                    'message' => "Video book not found while uploading videos.",
                ]);
            }

            // Get uploaded video for this book
            $videoCount = $videoBook->videos()->where('is_approved', Config::get('constant.APPROVED_VIDEO_FLAG'))->where('status', Config::get('constant.ACTIVE_FLAG'))->count();
            if ($videoCount >= Config::get('constant.MAX_VIDEOS_PER_BOOK')) {
                Log::error("Max video uploaded for this video book. Video Book Id: {$id}, Video Count: {$videoCount}");
                return Response::json([
                    'status' => 0,
                    'message' => "Max video uploaded for this video book.",
                ]);
            }

            if (count($request->file('videos')) > 0) {

                foreach ($request->file('videos') as $key => $file) {

                    $videoHelper = new VideoService();

                    $params = [
                        'originalPath' => Config::get('constant.SERIES_VIDEO_TEMP_UPLOAD_PATH'),
                        'imageOriginalPath' => Config::get('constant.SERIES_VIDEO_THUMB_UPLOAD_PATH'),
                    ];

                    $response = $videoHelper->save($request->file('videos')[$key], $params);

                    if ($response['status'] == 1) {

                        $info = $videoHelper->getInfo();

                        if (isset($info['status']) && $info['status'] == 0) {
                            return Response::json([
                                'status' => $info['status'],
                                'message' => $info['message'],
                            ]);
                        }

                        $videoId = $videoBook->videos()->save(new VideoBookVideo([
                            'title' => $info['filename'],
                            'path' => basename($info['file']),
                            'thumb_path' => basename($info['thumb']),
                            'type' => $request->type,
                            'lengh' => $info['duration'],
                            'size' => $info['filesize'] / 1024,
                            'is_transacoded' => Config::get('constant.TRANSCODING_PENDING_VIDEO_STATUS'),
                            'is_approved' => Config::get('constant.APPROVED_VIDEO_FLAG'),
                            'approved_by' => Auth::user()->id,
                            'approved_at' => Carbon::now(),
                            'status' => Config::get('constant.ACTIVE_FLAG'),
                        ])
                        );

                        return Response::json([
                            'status' => 1,
                            'message' => 'Video uploaded.',
                            'id' => $videoId->id,
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
                        ]);
                    } else {
                        return Response::json([
                            'status' => 0,
                            'message' => "VideoController: Error while video uploading... ({$response['message']})",
                        ]);
                    }
                }
            } else {
                Log::error("Video file is not found");
                return Response::json([
                    'status' => 0,
                    'message' => 'VideoController: Error while video uploading...',
                ]);
            }
        } catch (\Exception $e) {
            Log::error("Error while uploading video: {$e->getMessage()}");
            return Response::json([
                'status' => 0,
                'message' => "VideoController: Error while video uploading: {$e->getMessage()}",
            ]);
        }
    }

    /**
     * [setVideoAjax - Transcode video in video book]
     *
     * @param  [\Illuminate\Http\Request]  [$request]
     * @return [json]       [Success]
     */
    public function setVideoAjax(Request $request)
    {
        try {
            // Loop through video info to transcode
            foreach ($request->videosInfo as $videoInfo) {

                $video = VideoBookVideo::find($videoInfo['id']);

                // If video not found
                if ($video === null) {
                    continue;
                }

                // Dispatch job to process the video
                $this->dispatch(new TranscodeVideo($videoInfo));
            }

            return Response::json([
                'status' => 1,
                'message' => 'Video(s) are processing in background.',
            ]);
        } catch (\Exception $e) {
            Log::error("Error while processing video: {$e->getMessage()}");
            return Response::json([
                'status' => 0,
                'message' => "VideoController: Error while video processing: {$e->getMessage()}",
            ]);
        }
    }

    /**
     * [uploadIntroVideo - Upload intro video of video series]
     *
     * @param  [\Illuminate\Http\Request]  [$request]
     * @return [boolean]       [Success]
     */
    private function uploadIntroVideo (Request $request) {
        try {

            // Decrypt Id
            $id = UrlService::base64UrlDecode($request->id);

            $videoBook = VideoBook::find($id);

            if ($request->hasFile('introVideo')) {

                $videoHelper = new VideoService();

                $params = [
                    'originalPath' => Config::get('constant.SERIES_VIDEO_TEMP_UPLOAD_PATH'),
                    'imageOriginalPath' => Config::get('constant.SERIES_VIDEO_THUMB_UPLOAD_PATH'),
                ];

                $response = $videoHelper->save($request->file('introVideo'), $params);

                if ($response['status'] == 1) {

                    $info = $videoHelper->getInfo();

                    if (isset($info['status']) && $info['status'] == 0) {
                        return [
                            'status' => $info['status'],
                            'message' => $info['message'],
                        ];
                    }

                    $videoId = $videoBook->videos()->save(new VideoBookVideo([
                        'title' => $info['filename'],
                        'path' => basename($info['file']),
                        'thumb_path' => basename($info['thumb']),
                        'type' => Config::get('constant.INTRO_VIDEO'),
                        'lengh' => $info['duration'],
                        'size' => $info['filesize'] / 1024,
                        'is_transacoded' => Config::get('constant.TRANSCODING_PENDING_VIDEO_STATUS'),
                        'is_approved' => Config::get('constant.APPROVED_VIDEO_FLAG'),
                        'approved_by' => Auth::user()->id,
                        'approved_at' => Carbon::now(),
                        'status' => Config::get('constant.ACTIVE_FLAG'),
                    ])
                    );

                    return [
                        'status' => 1,
                        'message' => 'Video uploaded.',
                        'id' => $videoId->id,
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
                    ];
                } else {
                    return [
                        'status' => 0,
                        'message' => "Error while uploading intro video... ({$response['message']})",
                    ];
                }
            } else {
                Log::error("Video file is not found");
                return [
                    'status' => 0,
                    'message' => 'Error while uploading video...',
                ];
            }
        } catch (\Exception $e) {
            Log::error("VideoBookController (Intro Video): Error while uploading intro video for series #{$request->id}. Error: {$e->getMessage()}");
            return [
                'status' => 0,
                'message' => 'Error while uploading intro video...',
            ];
        }
    }

    /**
     * [getVideoAjax - Get video of video series]
     *
     * @param  [\Illuminate\Http\Request]  [$request]
     * @return [json]       [Success]
     */
    public function getVideoAjax(Request $request)
    {
        try {
            // Decrypt Id
            $id = UrlService::base64UrlDecode($request->videoSeriesId);

            $videoBook = VideoBook::find($id);

            // If video book not found
            if ($videoBook === null) {
                Log::error("Video Series not found while refreshing video list. Video Book Id: {$id}");
                return Response::json([
                    'status' => 0,
                    'message' => "Video series not found!.",
                ]);
            }

            // Get video list
            $videos = $videoBook->allVideos()->get();

            // Render view
            $html = view('admin.video-books.video-list', compact('videos'))->render();
            // All good so return the response
            return response()->json([
                'status' => 1,
                'html'=> $html
            ]);
        } catch (\Exception $e) {
            Log::error("VideoBookController::getVideoAjax | Error while fetching video list.Error: {$e->getMessage()}");
            return Response::json([
                'status' => 0,
                'message' => "Error while fetching video list.",
            ]);
        }
    }

    /**
     * [saveVideoAjax - Save video detail of video series]
     *
     * @param  [\Illuminate\Http\Request]  [$request]
     * @return [json]       [Success]
     */
    public function saveVideoAjax(Request $request)
    {
        try {
            // Decrypt Id
            $id = UrlService::base64UrlDecode($request->video_id);

            $video = VideoBookVideo::find($id);

            // If video not found
            if ($video === null) {
                Log::error("Video not found while updating video detail. Video Id: {$id}");
                return Response::json([
                    'status' => 0,
                    'message' => "Video not found!.",
                ]);
            }

            // Update video list
            $video->update([
                'title' => $request->video_name,
                'descr' => $request->video_descr
            ]);

            // All good so return the response
            return response()->json([
                'status' => 1,
                'message'=> "Video detail updated successfully."
            ]);
        } catch (\Exception $e) {
            Log::error("VideoBookController::saveVideoAjax | Error while updating video detail. Error: {$e->getMessage()}");
            return Response::json([
                'status' => 0,
                'message' => "Error while updating video detail.",
            ]);
        }
    }
    /**
     * [removeVideoAjax - Remove video of video series]
     *
     * @param  [\Illuminate\Http\Request]  [$request]
     * @return [json]       [Success]
     */
    public function removeVideoAjax(Request $request)
    {
        try {
            // Decrypt Id
            $id = UrlService::base64UrlDecode($request->video_id);

            $video = VideoBookVideo::find($id);

            // If video not found
            if ($video === null) {
                Log::error("Video not found while deleting. Video Id: {$id}");
                return Response::json([
                    'status' => 0,
                    'message' => "Video not found!.",
                ]);
            }

            // Update video list
            $video->delete();

            // All good so return the response
            return response()->json([
                'status' => 1,
                'message'=> "Video deleted successfully."
            ]);
        } catch (\Exception $e) {
            Log::error("VideoBookController::removeVideoAjax | Error while deleting video. Error: {$e->getMessage()}");
            return Response::json([
                'status' => 0,
                'message' => "Error while deleting video.",
            ]);
        }
    }

    public function approveVideoAjax(Request $request){
        try {
            // Decrypt Id
            $id = UrlService::base64UrlDecode($request->video_id);

            $video = VideoBookVideo::find($id);

            // If video not found
            if ($video === null) {
                Log::error("Video not found while approving. Video Id: {$id}");
                return Response::json([
                    'status' => 0,
                    'message' => "Video not found!.",
                ]);
            }

            // Update video list
            $video->is_approved = 1;
            $video->approved_by = $request->user()->id;
            $video->approved_at	= Carbon::now();
            $video->save();

            // All good so return the response
            return response()->json([
                'status' => 1,
                'message'=> "Video Approved successfully."
            ]);
        } catch (\Exception $e) {
            Log::error("VideoBookController::approveVideoAjax | Error while approving video. Error: {$e->getMessage()}");
            return Response::json([
                'status' => 0,
                'message' => "Error while approving video.",
            ]);
        }
    }

    public function rejectVideoAjax(Request $request){
        try {
            // Decrypt Id
            $id = UrlService::base64UrlDecode($request->video_id);

            $video = VideoBookVideo::find($id);

            // If video not found
            if ($video === null) {
                Log::error("Video not found while reject. Video Id: {$id}");
                return Response::json([
                    'status' => 0,
                    'message' => "Video not found!.",
                ]);
            }

            // Update video list
            $video->is_approved = 0;
            $video->approved_by = null;
            $video->approved_at	= null;
            $video->save();

            // All good so return the response
            return response()->json([
                'status' => 1,
                'message'=> "Video Reject successfully."
            ]);
        } catch (\Exception $e) {
            Log::error("VideoBookController::rejectVideoAjax | Error while reject video. Error: {$e->getMessage()}");
            return Response::json([
                'status' => 0,
                'message' => "Error while reject video.",
            ]);
        }
    }

    public function pendingVideos(Request $request){
        try{
            return view('admin.pending-video.list');
        } catch (\Exception $e) {
            Log::error(strtr(trans('log-messages.VIDEO_BOOK_ADD_EDIT_ERROR_MESSAGE'), [
                '<Message>' => $e->getMessage(),
            ]));
            return Redirect::to("admin/video-books")->with('error', trans('admin-message.DEFAULT_ERROR_MESSAGE'));
        }
    }

    public function pendingVideosList(Request $request){
        $records = array();
        if ($request->customActionType == 'groupAction') {

            $action = $request->customActionName;
            $idArray = $request->id;

            if ($action == 'status') {
                foreach ($idArray as $_idArray) {
                    $_idArray = UrlService::base64UrlDecode($_idArray);
                    VideoBookVideo::updateVideoBookVideosStatus($_idArray);
                }
                $records["message"] = trans('admin-message.VIDEO_APPROVED_SUCCESSFULLY');
            }
        }

        $columns = array(
            0 => 'title',
            1 => 'descr',
            2 => 'video_book_title',
            3 => 'full_name',
            4 => 'thumb',
            5 => 'is_transacoded',
            6 => 'status',
        );
        $order = $request->order;
        $search = $request->search;
        $records["data"] = array();

        // Getting records from the VideoBookVideo table
        $records["data"] = VideoBookVideo::leftJoin('e_video_book', 'e_video_book.id', '=', 'e_video_book_videos.e_video_book_id')
            ->leftJoin('users', 'users.id', '=', 'e_video_book.user_id')
            ->where('e_video_book.status', '<>', Config::get('constant.DELETED_FLAG'))
            ->where('e_video_book_videos.is_approved', 0)
            ->where('e_video_book_videos.type', Config::get('constant.SERIES_VIDEO'))
            ->whereNotNull('e_video_book_videos.completed_at');

        $iTotalRecords = $records["data"]->count();
        $iTotalFiltered = $iTotalRecords;
        $iDisplayLength = intval($request->length) <= 0 ? $iTotalRecords : intval($request->length);
        $iDisplayStart = intval($request->start);
        $sEcho = intval($request->draw);

        if (!empty($search['value'])) {
            $val = $search['value'];
            $records["data"]->where(function ($query) use ($val) {
                $query->SearchVideoTitle($val)
                    ->SearchVideoTranscodeStatus($val)
                    ->SearchVideoAuthor($val)
                    ->SearchVideoBookTitle($val);
            });

            // No of record after filtering
            $iTotalFiltered = $records["data"]->where(function ($query) use ($val) {
                $query->SearchVideoTitle($val)
                    ->SearchVideoTranscodeStatus($val)
                    ->SearchVideoAuthor($val)
                    ->SearchVideoBookTitle($val);
            })->count();
        }

        //order by
        foreach ($order as $o) {
            $records["data"] = $records["data"]->orderBy($columns[$o['column']], $o['dir']);
        }

        // Get Record
        $records["data"] = $records["data"]->take($iDisplayLength)->offset($iDisplayStart)->get([
            'e_video_book_videos.id',
            'e_video_book_videos.title',
            'e_video_book_videos.descr',
            'e_video_book_videos.is_transacoded',
            'e_video_book_videos.is_approved as status',
            'e_video_book_videos.thumb_path as thumb',
            'e_video_book_videos.path',
            'e_video_book.id as video_book_id',
            'e_video_book.title as video_book_title',
            'users.full_name',
        ]);

        if (!empty($records["data"])) {
            foreach ($records["data"] as $key => $_records) {
                $id = UrlService::base64UrlEncode($_records->id);
                $edit = route('video-books.edit', $id);
                $url = route('get-video_signed-url', $_records->id);
                if ($_records->is_approved == 0) {
                    $records["data"][$key]->status = '<a href="javascript:;" title="Make Approve" class="btn-status-video-books" data-id="' . $id . '">Pending</a>';
                } else {
                    $records["data"][$key]->status = '<a href="javascript:;" title="Make Pending" class="btn-status-video-books" data-id="' . $id . '"> Approved</a>';
                }
                $image = !empty($_records->thumb) ? Storage::disk(env('FILESYSTEM_DRIVER'))->url($this->videosThumbPath . $_records->thumb)  : asset('images/default_user_profile.png');
                $records["data"][$key]['thumb'] = '<img src ='.$image.' height=50px width=50px>';
                if ($_records->is_transacoded == Config::get('constant.TRANSCODING_DONE_VIDEO_STATUS')){
                    $object_key = Config::get('constant.SERIES_VIDEO_UPLOAD_PATH') . $_records->path;
                    $video_url = Helpers::generateAWSSignedUrl($object_key);
                    $records["data"][$key]['action'] = '<a class="btn btn-info" href="'.$video_url.'" target="_blank"><i class="icon-eye"></i></button>';
                }elseif($_records->is_transacoded == Config::get('constant.TRANSCODING_FAILED_VIDEO_STATUS')){
                    $object_key = Config::get('constant.SERIES_VIDEO_UPLOAD_PATH') . $_records->path;
                    $video_url = Helpers::generateAWSSignedUrl($object_key);
                    $records["data"][$key]['action'] = '<a class="btn btn-info" href="'.$video_url.'" target="_blank"><i class="icon-eye"></i></button>';
                } else{
                    $records["data"][$key]['action'] = '-';
                }
            }
        }
        $records["draw"] = $sEcho;
        $records["recordsTotal"] = $iTotalRecords;
        $records["recordsFiltered"] = $iTotalFiltered;
        return Response::json($records);
    }

    public function getVideoSignedUrl($id){
        try{
            $video = VideoBookVideo::find($id);
            if (!$video){
                echo "Video Not Found";
            }
            if ($video->is_transacoded == Config::get('constant.TRANSCODING_DONE_VIDEO_STATUS')){
                $object_key = Config::get('constant.SERIES_VIDEO_UPLOAD_PATH') . $video->path;
                $video_url = Helpers::generateAWSSignedUrl($object_key);
            } elseif ($video->is_transacoded == Config::get('constant.TRANSCODING_FAILED_VIDEO_STATUS')){
                $object_key = Config::get('constant.SERIES_VIDEO_TEMP_UPLOAD_PATH') . $video->path;
                $video_url = Helpers::generateAWSSignedUrl($object_key);
            } else{
                $video_url = Storage::disk('public')->url(Config::get('constant.SERIES_VIDEO_TEMP_UPLOAD_PATH') . $video->path);
            }

            echo '<video width="320" height="240" controls>
                        <source src="'.$video_url.'" type="video/mp4">
                        Your browser does not support the video tag.
                    </video>';
        } catch (\Exception $e){
            echo 'something went wrong';
        }
    }
}
