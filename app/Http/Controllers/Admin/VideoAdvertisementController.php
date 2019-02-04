<?php

namespace App\Http\Controllers\Admin;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Models\VideoAdvertisement;
use App\Services\UrlService;
use Illuminate\Validation\Rule;
use App\Services\ImageUpload;
use Auth;
use Config;
use DB;
use Log;
use Redirect;
use Response;
use Validator;

class VideoAdvertisementController extends Controller
{
    public function __construct()
    {
        $this->videpOriginalImagePath = Config::get('constant.VIDEO_ADVERTISEMENT_ORIGINAL_UPLOAD_PATH');
    }


    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        return view('admin.video-advertisement.list');
    }

    /**
     * [listAjax List VideoBook]
     * @param  [type]       [description]
     * @return [json]       [list of VideoBook]
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
                    $this->deleteVideoAdvertisement($_idArray);
                }
                $records["message"] = trans('admin-message.VIDEO_ADVERTISEMENT_DELETED_SUCCESSFULLY_MESSAGE');
            }
            if ($action == 'status') {
                foreach ($idArray as $_idArray) {
                    $_idArray = UrlService::base64UrlDecode($_idArray);
                    VideoAdvertisement::updateVideoAdvertisementStatus($_idArray);
                }
                $records["message"] = trans('admin-message.VIDEO_BOOK_STATUS_UPDATED_SUCCESS_MESSAGE');
            }
        }

        $columns = array(
            0 => 'title',
            1 => 'descr',
            2 => 'is_skipable',
            3 => 'size',
            4 => 'position',
            5 => 'status',
        );
        $order = $request->order;
        $search = $request->search;
        $records["data"] = array();

        // Getting records from the VideoAdvertisement table
        $iTotalRecords = VideoAdvertisement::getVideoAdvertisementCount();
        $iTotalFiltered = $iTotalRecords;
        $iDisplayLength = intval($request->length) <= 0 ? $iTotalRecords : intval($request->length);
        $iDisplayStart = intval($request->start);
        $sEcho = intval($request->draw);

        $records["data"] = VideoAdvertisement::where('status', '<>', Config::get('constant.DELETED_FLAG'));

        if (!empty($search['value'])) {
            $val = $search['value'];
            $records["data"]->where(function ($query) use ($val) {
                $query->SearchVideoAdvertisementTitle($val)
                    ->SearchVideoAdvertisementDescription($val)
                    ->SearchVideoAdvertisementStatus($val);
            });

            // No of record after filtering
            $iTotalFiltered = $records["data"]->where(function ($query) use ($val) {
                 $query->SearchVideoAdvertisementTitle($val)
                    ->SearchVideoAdvertisementDescription($val)
                    ->SearchVideoAdvertisementStatus($val);
            })->count();
        }

        //order by
        foreach ($order as $o) {
            $records["data"] = $records["data"]->orderBy($columns[$o['column']], $o['dir']);
        }

        // Get Record
        $records["data"] = $records["data"]->take($iDisplayLength)->offset($iDisplayStart)->get();

        if (!empty($records["data"])) {
            foreach ($records["data"] as $key => $_records) {
                $id = UrlService::base64UrlEncode($_records->id);
                $edit = route('video-advertisement.edit', $id);
                if ($_records->status == "active") {
                    $records["data"][$key]->status = '<a href="javascript:;" title="Make Inactive" class="btn-status-video-advertisement" data-id="' . $id . '"> ' . $_records->status . '</a>';
                } else {
                    $records["data"][$key]->status = '<a href="javascript:;" title="Make Active" class="btn-status-video-advertisement" data-id="' . $id . '"> ' . $_records->status . '</a>';
                }
                $records["data"][$key]['action'] = "&emsp;<a href='{$edit}' title='Edit Video Advertisement' ><span class='menu-icon icon-pencil'></span></a>
                                                    &emsp;<a href='javascript:;' data-id='" . $id . "' class='btn-delete-video-advertisement' title='Delete Video Advertisement'><span class='menu-icon icon-trash'></span></a>";
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
        $videoAdvertisement = VideoAdvertisement::find($id);
        if ($videoAdvertisement === null) {
            return Redirect::to("/admin/video-category")->with('error', trans('admin-message.VIDEO_CATEGORY_NOT_EXIST'));
        }
        return $this->_update($videoAdvertisement);
    }

    /**
     * Create / Update controller
     * @param $user = VideoCategory object
     */
    private function _update($videoAdvertisement = null)
    {
        if ($videoAdvertisement == null) {
            $videoAdvertisement = new VideoAdvertisement();
        }
        $status = Config::get('constant.STATUS');
        $position = Config::get('constant.POSITION');
        return view('admin.video-advertisement.add', compact('videoAdvertisement', 'status','position'));
    }

    /**
     * Store a resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function set(Request $request)
    {

        DB::beginTransaction();
        try {
            // Decrypt Id
            $id = UrlService::base64UrlDecode($request->id);

            // Rule Validation - Start
            $rule = [   
                    'title' => 'required|max:100', // |regex:/^(?![0-9]*$)[a-zA-Z0-9 ]+$/
                    'descr' => 'required',
                    'path' => 'mimes:mp4|max:20000',
                    'position' => 'required', 
                    'is_skipable' => 'required',
                    'skipale_after'=>'nullable',
                    'status' => ['required', Rule::in([Config::get('constant.ACTIVE_FLAG'), Config::get('constant.INACTIVE_FLAG')])],
                ];
            
            if ($id == 0 || empty($id)){
                $rule['path'] = 'required|mimes:mp4|max:20000';
            }

            $validator = Validator::make($request->all(), $rule);

            if ($validator->fails()) {
                return Redirect::back()->withInput($request->all())->withErrors($validator);
            }

            // Rule Validation - End

            // Get Video Advertisement
            $videoAdvertisement = VideoAdvertisement::find($id);
            $path = $videoAdvertisement['path'];

            $postData = $request->only('title', 'descr','path','is_skipable','position','skipale_after', 'status');

            if (!empty($request->file('path')) && $request->file('path')->isValid()) {
                $params = [
                    'originalPath' => $this->videpOriginalImagePath,
                    'previousVideo'=>$path
                ];
                $videoUpload = ImageUpload::uploadVideo($request->file('path'), $params);
                if ($videoUpload === false) {
                    DB::rollback();
                    // Log the error
                    Log::error(strtr(trans('log-messages.ADVERTISEMENT_VIDEO_UPLOAD_ERROR_MESSAGE'), [
                        '<Message>' => $e->getMessage(),
                    ]));

                    return Redirect::back()->withInput($request->all())->with('error', trans('admin-message.ADVERTISEMENT_VIDEO_UPLOAD_ERROR_MESSAGE'));
                }
                $postData['path'] = $videoUpload['videoName'];
                $postData['size'] = $videoUpload['size'];
                // $postData['duration'] = $videoUpload['duration'];
            }

            if (isset($request->id) && UrlService::base64UrlDecode($request->id) > 0) {
                $videoAdvertisement->update($postData);
                DB::commit();

                return Redirect::to("admin/video-advertisement")->with('success', trans('admin-message.VIDEO_ADVERTISEMENT_UPDATED_SUCCESSFULLY_MESSAGE'));
            } else {             
                $videoAdvertisement = new VideoAdvertisement($postData);
                $videoAdvertisement->save();
                DB::commit();
                return Redirect::to("admin/video-advertisement")->with('success', trans('admin-message.VIDEO_ADVERTISEMENT_CREATED_SUCCESSFULLY_MESSAGE'));
            }
        } catch (\Exception $e) {
            DB::rollback();
            Log::error(strtr(trans('log-messages.VIDEO_ADVERTISEMENT_ADD_EDIT_ERROR_MESSAGE'), [
                '<Message>' => $e->getMessage(),
            ]));
            return Redirect::to("admin/video-advertisement")->with('error', trans('admin-message.DEFAULT_ERROR_MESSAGE'));
        }
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return Boolean
     */
    public function deleteVideoAdvertisement($id)
    {
        try {
            $videoAdvertisement = VideoAdvertisement::findOrFail($id);

            // If user not found
            if ($videoAdvertisement === null) {
                Log::error(strtr(trans('log-messages.VIDEO_BOOK_DELETE_ERROR_MESSAGE'), [
                    '<Message>' => trans('admin-message.VIDEO_BOOK_YOU_WANT_TO_DELETE_IS_NOT_FOUND'),
                ]));
                return 0;
            }
            $videoAdvertisement->delete();
            return 1;
        } catch (\Exception $e) {
            Log::error(strtr(trans('log-messages.VIDEO_ADVERTISEMENT_DELETE_ERROR_MESSAGE'), [
                '<Message>' => $e->getMessage(),
            ]));
            return 0;
        }
    }
}
