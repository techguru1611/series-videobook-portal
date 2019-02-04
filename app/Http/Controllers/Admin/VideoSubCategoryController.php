<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use App\Models\VideoCategory;
use App\Models\VideoSubCategory;
use App\Services\UrlService;
use Config;
use DB;
use Log;
use Redirect;
use Response;
use Validator;

class VideoSubCategoryController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        return view('admin.video-sub-category.list');
    }

    /**
     * [listAjax List VideoSubCategory]
     * @param  [type]       [description]
     * @return [json]       [list of VideoSubCategory]
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
                    $this->deleteVideoSubCategory($_idArray);
                }
                $records["message"] = trans('admin-message.VIDEO_SUB_CATEGORY_DELETED_SUCCESSFULLY_MESSAGE');
            }
            if ($action == 'status') {
                foreach ($idArray as $_idArray) {
                    $_idArray = UrlService::base64UrlDecode($_idArray);
                    VideoSubCategory::updateVideoSubCategoryStatus($_idArray);
                }
                $records["message"] = trans('admin-message.VIDEO_SUB_CATEGORY_STATUS_UPDATED_SUCCESS_MESSAGE');
            }
        }

        $columns = array(
            0 => 'video_category_title',
            1 => 'title',
            2 => 'descr',
            3 => 'status',
        );
        $order = $request->order;
        $search = $request->search;
        $records["data"] = array();

        // Getting records from the VideoSubCategory table
        $iTotalRecords = VideoSubCategory::getVideoSubCategoryCount();
        $iTotalFiltered = $iTotalRecords;
        $iDisplayLength = intval($request->length) <= 0 ? $iTotalRecords : intval($request->length);
        $iDisplayStart = intval($request->start);
        $sEcho = intval($request->draw);

        $records["data"] = VideoSubCategory::leftJoin('video_category', 'video_category.id', '=', 'video_sub_category.video_category_id')
            ->where('video_sub_category.status', '<>', Config::get('constant.DELETED_FLAG'));

        if (!empty($search['value'])) {
            $val = $search['value'];
            $records["data"]->where(function ($query) use ($val) {
                $query->SearchVideoCategoryTitle($val)
                    ->SearchVideoSubCategoryTitle($val)
                    ->SearchVideoSubCategoryDescription($val)
                    ->SearchVideoSubCategoryStatus($val);
            });

            // No of record after filtering
            $iTotalFiltered = $records["data"]->where(function ($query) use ($val) {
                $query->SearchVideoCategoryTitle($val)
                    ->SearchVideoSubCategoryTitle($val)
                    ->SearchVideoSubCategoryDescription($val)
                    ->SearchVideoSubCategoryStatus($val);
            })->count();
        }

        //order by
        foreach ($order as $o) {
            $records["data"] = $records["data"]->orderBy($columns[$o['column']], $o['dir']);
        }

        // Get Record
        $records["data"] = $records["data"]->take($iDisplayLength)->offset($iDisplayStart)->get([
            'video_sub_category.id',
            'video_sub_category.title',
            'video_sub_category.descr',
            'video_sub_category.status',
            'video_category.title AS video_category_title',
        ]);

        if (!empty($records["data"])) {
            foreach ($records["data"] as $key => $_records) {
                $id = UrlService::base64UrlEncode($_records->id);
                $edit = route('video-sub-category.edit', $id);
                if ($_records->status == "active") {
                    $records["data"][$key]->status = '<a href="javascript:;" title="Make Inactive" class="btn-status-video-sub-category" data-id="' . $id . '"> ' . $_records->status . '</a>';
                } else {
                    $records["data"][$key]->status = '<a href="javascript:;" title="Make Active" class="btn-status-video-sub-category" data-id="' . $id . '"> ' . $_records->status . '</a>';
                }
                $records["data"][$key]['action'] = "&emsp;<a href='{$edit}' title='Edit Video Sub Category' ><span class='menu-icon icon-pencil'></span></a>
                                                    &emsp;<a href='javascript:;' data-id='" . $id . "' class='btn-delete-video-sub-category' title='Delete Video Sub Category' ><span class='menu-icon icon-trash'></span></a>";
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
        // Get Active Video category
        $videoCategory = VideoCategory::getActiveVideoCategory();
        return $this->_update($videoCategory);
    }

    /**
     * Update controller
     * @param $id = VideoSubCategory id
     */
    public function update($id)
    {
        $id = UrlService::base64UrlDecode($id);
        $videoSubCategory = VideoSubCategory::find($id);
        if ($videoSubCategory === null) {
            return Redirect::to("/admin/video-sub-category")->with('error', trans('admin-message.VIDEO_SUB_CATEGORY_NOT_EXIST'));
        }
        // Get Video category
        $videoCategory = VideoCategory::getVideoCategory();

        return $this->_update($videoCategory, $videoSubCategory);
    }

    /**
     * Create / Update controller
     * @param $user = VideoSubCategory object
     */
    private function _update($videoCategory, $videoSubCategory = null)
    {
        if ($videoSubCategory == null) {
            $videoSubCategory = new VideoSubCategory();
        }
        $status = Config::get('constant.STATUS');
        return view('admin.video-sub-category.add', compact('videoCategory', 'videoSubCategory', 'status'));
    }

    /**
     * Store a resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function set(Request $request)
    {
        try {
            // Rule Validation - Start
            $rule = [
                'video_category_id' => 'required|integer|min:1',
                'title' => 'required|max:100', // |regex:/^(?![0-9]*$)[a-zA-Z0-9 ]+$/
                'descr' => 'required',
                'status' => ['required', Rule::in([Config::get('constant.ACTIVE_FLAG'), Config::get('constant.INACTIVE_FLAG')])],
            ];

            $validator = Validator::make($request->all(), $rule);

            if ($validator->fails()) {
                return Redirect::back()->withInput($request->all())->withErrors($validator);
            }

            // Rule Validation - End

            // Decrypt Id
            $id = UrlService::base64UrlDecode($request->id);

            // Get VideoSubCategory
            $videoSubCategory = VideoSubCategory::find($id);

            $postData = $request->only('video_category_id', 'title', 'descr', 'status');
            if (isset($request->id) && UrlService::base64UrlDecode($request->id) > 0) {
                $videoSubCategory->update($postData);
                DB::commit();
                return Redirect::to("admin/video-sub-category")->with('success', trans('admin-message.VIDEO_SUB_CATEGORY_UPDATED_SUCCESSFULLY_MESSAGE'));
            } else {
                $videoSubCategory = new VideoSubCategory($postData);
                $videoSubCategory->save();
                DB::commit();
                return Redirect::to("admin/video-sub-category")->with('success', trans('admin-message.VIDEO_SUB_CATEGORY_CREATED_SUCCESSFULLY_MESSAGE'));
            }
        } catch (\Exception $e) {
            DB::rollback();
            Log::error(strtr(trans('log-messages.VIDEO_SUB_CATEGORY_ADD_EDIT_ERROR_MESSAGE'), [
                '<Message>' => $e->getMessage(),
            ]));
            return Redirect::to("admin/video-sub-category")->with('error', trans('admin-message.DEFAULT_ERROR_MESSAGE'));
        }
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return Boolean
     */
    public function deleteVideoSubCategory($id)
    {
        try {
            $videoSubCategory = VideoSubCategory::findOrFail($id);

            // If user not found
            if ($videoSubCategory === null) {
                Log::error(strtr(trans('log-messages.VIDEO_SUB_CATEGORY_DELETE_ERROR_MESSAGE'), [
                    '<Message>' => trans('admin-message.VIDEO_SUB_CATEGORY_YOU_WANT_TO_DELETE_IS_NOT_FOUND'),
                ]));
                return 0;
            }
            $videoSubCategory->delete();
            return 1;
        } catch (\Exception $e) {
            Log::error(strtr(trans('log-messages.VIDEO_SUB_CATEGORY_DELETE_ERROR_MESSAGE'), [
                '<Message>' => $e->getMessage(),
            ]));
            return 0;
        }
    }
}
