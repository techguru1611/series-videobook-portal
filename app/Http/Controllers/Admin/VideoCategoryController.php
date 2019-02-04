<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\VideoCategory;
use App\Services\UrlService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use App\Services\ImageUpload;
use Log;
use Config;
use DB;
use Redirect;
use Response;
use Validator;

class VideoCategoryController extends Controller
{
    public function __construct()
    {
        $this->videoCategoryOriginalImagePath = Config::get('constant.VIDEO_CATEGORY_ORIGINAL_PHOTO_UPLOAD_PATH');
        $this->videoCategoryThumbImagePath = Config::get('constant.VIDEO_CATEGORY_THUMB_PHOTO_UPLOAD_PATH');
        $this->videoCategoryThumbImageHeight = Config::get('constant.VIDEO_CATEGORY_THUMB_PHOTO_HEIGHT');
        $this->videoCategoryThumbImageWidth = Config::get('constant.VIDEO_CATEGORY_THUMB_PHOTO_WIDTH');
    }
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        return view('admin.video-category.list');
    }

    /**
     * [listAjax List VideoCategory]
     * @param  [type]       [description]
     * @return [json]       [list of VideoCategory]
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
                    $this->deleteVideoCategory($_idArray);
                }
                $records["message"] = trans('admin-message.VIDEO_CATEGORY_DELETED_SUCCESSFULLY_MESSAGE');
            }
            if ($action == 'status') {
                foreach ($idArray as $_idArray) {
                    $_idArray = UrlService::base64UrlDecode($_idArray);
                    VideoCategory::updateVideoCategoryStatus($_idArray);
                }
                $records["message"] = trans('admin-message.VIDEO_CATEGORY_STATUS_UPDATED_SUCCESS_MESSAGE');
            }
        }

        $columns = array(
            0 => 'title',
            1 => 'descr',
            2 => 'status',
        );
        $order = $request->order;
        $search = $request->search;
        $records["data"] = array();

        // Getting records from the VideoCategory table
        $iTotalRecords = VideoCategory::getVideoCategoryCount();
        $iTotalFiltered = $iTotalRecords;
        $iDisplayLength = intval($request->length) <= 0 ? $iTotalRecords : intval($request->length);
        $iDisplayStart = intval($request->start);
        $sEcho = intval($request->draw);

        $records["data"] = VideoCategory::where('status', '<>', Config::get('constant.DELETED_FLAG'));

        if (!empty($search['value'])) {
            $val = $search['value'];
            $records["data"]->where(function ($query) use ($val) {
                $query->SearchVideoCategoryTitle($val)
                    ->SearchVideoCategoryDescription($val)
                    ->SearchVideoCategoryStatus($val);
            });

            // No of record after filtering
            $iTotalFiltered = $records["data"]->where(function ($query) use ($val) {
                $query->SearchVideoCategoryTitle($val)
                    ->SearchVideoCategoryDescription($val)
                    ->SearchVideoCategoryStatus($val);
            })->count();
        }

        //order by
        foreach ($order as $o) {
            $records["data"] = $records["data"]->orderBy($columns[$o['column']], $o['dir']);
        }

        // Get Record
        $records["data"] = $records["data"]->take($iDisplayLength)->offset($iDisplayStart)->get([
            'id',
            'title',
            'descr',
            'image',
            'status'
        ]);

        if (!empty($records["data"])) {
            foreach ($records["data"] as $key => $_records) {
                $id = UrlService::base64UrlEncode($_records->id);
                $edit = route('video-category.edit', $id);
                if ($_records->status == "active") {
                    $records["data"][$key]->status = '<a href="javascript:;" title="Make Inactive" class="btn-status-video-category" data-id="' . $id . '"> ' . $_records->status . '</a>';
                } else {
                    $records["data"][$key]->status = '<a href="javascript:;" title="Make Active" class="btn-status-video-category" data-id="' . $id . '"> ' . $_records->status . '</a>';
                }
                $descr = strlen($_records->descr) > 50 ? substr($_records->descr,0,50)."..." : $_records->descr;
                $records["data"][$key]['descr'] = $descr;
                $image = !empty($_records->image) ? Storage::disk(env('FILESYSTEM_DRIVER'))->url($this->videoCategoryThumbImagePath . $_records->image)  : asset('images/default_user_profile.png');
                $records["data"][$key]['image'] = '<img src ='. $image .' height=50px width=50px>';
                $records["data"][$key]['action'] = "&emsp;<a href='{$edit}' title='Edit Video Category' ><span class='menu-icon icon-pencil'></span></a>
                                                    &emsp;<a href='javascript:;' data-id='" . $id . "' class='btn-delete-video-category' title='Delete Video Category' ><span class='menu-icon icon-trash'></span></a>";
            }
        }
        $records["draw"] = $sEcho;
        $records["recordsTotal"] = $iTotalRecords;
        $records["recordsFiltered"] = $iTotalFiltered;
        return Response::json($records);
    }

    /**
     * Create controller
     */
    public function create()
    {
        return $this->_update();
    }

    /**
     * Update controller
     * @param $id = VideoCategory id
     */
    public function update($id)
    {
        $id = UrlService::base64UrlDecode($id);
        $videoCategory = VideoCategory::find($id);
        if ($videoCategory === null) {
            return Redirect::to("/admin/video-category")->with('error', trans('admin-message.VIDEO_CATEGORY_NOT_EXIST'));
        }
        return $this->_update($videoCategory);
    }

    /**
     * Create / Update controller
     * @param $user = VideoCategory object
     */
    private function _update($videoCategory = null)
    {
        if ($videoCategory == null) {
            $videoCategory = new VideoCategory();
        }
        $status = Config::get('constant.STATUS');
        return view('admin.video-category.add', compact('videoCategory', 'status'));
    }

    /**
     * Store a resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function set(Request $request)
    {
        // dd($request->all());
        DB::beginTransaction();
        try {
            // Decrypt Id
            $id = UrlService::base64UrlDecode($request->id);

            // Rule Validation - Start
            $rule = [
                'title' => 'required|max:100', // |regex:/^(?![0-9]*$)[a-zA-Z0-9 ]+$/
                'descr' => 'required',
                'image' => 'image',
                'status' => ['required', Rule::in([Config::get('constant.ACTIVE_FLAG'), Config::get('constant.INACTIVE_FLAG')])],
            ];

            if ($id == 0 || empty($id)){
                $rule['image'] = 'required|image';
            }

            $validator = Validator::make($request->all(), $rule);

            if ($validator->fails()) {
                return Redirect::back()->withInput($request->all())->withErrors($validator);
            }
            // Rule Validation - End

            
            // Get VideoCategory
            $videoCategory = VideoCategory::find($id);
            $path = $videoCategory['image'];

            $postData = $request->only('title', 'descr','image','status');


            // Upload category Photo
            if (!empty($request->file('image')) && $request->file('image')->isValid()) {
                $params = [
                    'originalPath' => $this->videoCategoryOriginalImagePath,
                    'thumbPath' => $this->videoCategoryThumbImagePath,
                    'thumbHeight' => $this->videoCategoryThumbImageHeight,
                    'thumbWidth' => $this->videoCategoryThumbImageWidth,
                    'previousImage' => $path,
                ];
                $videoCategoryImage = ImageUpload::uploadWithThumbImage($request->file('image'), $params);
                
                if ($videoCategoryImage === false) {
                    DB::rollback();

                    // Log the error
                    Log::error(strtr(trans('log-messages.VIDEO_CATEGORY_UPLOAD_ERROR_MESSAGE'), [
                        '<Message>' => $e->getMessage(),
                    ]));

                    return Redirect::back()->withInput($request->all())->with('error', trans('admin-message.VIDEO_CATEGORY_UPLOAD_ERROR_MSG'));
                } else {
                    $postData['image'] = $videoCategoryImage['imageName'];
                }
            }

            if (isset($request->id) && UrlService::base64UrlDecode($request->id) > 0) {
                $videoCategory->update($postData);
                DB::commit();
                return Redirect::to("admin/video-category")->with('success', trans('admin-message.VIDEO_CATEGORY_UPDATED_SUCCESSFULLY_MESSAGE'));
            } else {
                $videoCategory = new VideoCategory($postData);
                $videoCategory->save();
                DB::commit();
                return Redirect::to("admin/video-category")->with('success', trans('admin-message.VIDEO_CATEGORY_CREATED_SUCCESSFULLY_MESSAGE'));
            }
        } catch (\Exception $e) {
            DB::rollback();
            Log::error(strtr(trans('log-messages.VIDEO_CATEGORY_ADD_EDIT_ERROR_MESSAGE'), [
                '<Message>' => $e->getMessage(),
            ]));
            return Redirect::to("admin/video-category")->with('error', trans('admin-message.DEFAULT_ERROR_MESSAGE'));
        }
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return Boolean
     */
    public function deleteVideoCategory($id)
    {
        try {
            $videoCategory = VideoCategory::findOrFail($id);

            // If user not found
            if ($videoCategory === null) {
                Log::error(strtr(trans('log-messages.VIDEO_CATEGORY_DELETE_ERROR_MESSAGE'), [
                    '<Message>' => trans('admin-message.VIDEO_CATEGORY_YOU_WANT_TO_DELETE_IS_NOT_FOUND'),
                ]));
                return 0;
            }
            $videoCategory->delete();
            return 1;
        } catch (\Exception $e) {
            Log::error(strtr(trans('log-messages.VIDEO_CATEGORY_DELETE_ERROR_MESSAGE'), [
                '<Message>' => $e->getMessage(),
            ]));
            return 0;
        }
    }
}
