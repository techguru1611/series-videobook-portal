<?php

namespace App\Http\Controllers\Admin;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Validation\Rule;
use App\Services\UrlService;
use App\Models\Setting;
use App\Models\VideoBook;
use Auth;
use Config;
use DB;
use Log;
use Redirect;
use Response;
use Validator;

class SettingsController extends Controller
{
     /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        return view('admin.setting.list');
    }

     /**
     * [listAjax List Setting]
     * @param  [type]   [description]
     * @return [json]   [list of settings]
     */
    public function listAjax(Request $request)
    {
        $records = array();
        if ($request->customActionType == 'groupAction') {
            $action = $request->customActionName;
            $idArray = $request->id;
            if ($action == 'status') {
                foreach ($idArray as $_idArray) {
                    $_idArray = UrlService::base64UrlDecode($_idArray);
                    Setting::updateSettingStatus($_idArray);
                }
                $records["message"] = trans('admin-message.SETTING_STATUS_UPDATED_SUCCESS_MESSAGE');
            }
        }
        $columns = array(
            0 => 'title',
            1 => 'slug',
            2 => 'status',
        );
        $order = $request->order;
        $search = $request->search;
        $records["data"] = array();

        // Getting records from the settings table
        $iTotalRecords = Setting::getSettingCount();
        $iTotalFiltered = $iTotalRecords;
        $iDisplayLength = intval($request->length) <= 0 ? $iTotalRecords : intval($request->length);
        $iDisplayStart = intval($request->start);
        $sEcho = intval($request->draw);

        // $records["data"] = Setting::where('status', '<>', Config::get('constant.DELETED_FLAG'));

         $records["data"] = Setting::leftJoin('e_video_book', 'e_video_book.id', '=', 'settings.value')->where('settings.status', '<>', Config::get('constant.DELETED_FLAG'));

        if (!empty($search['value'])) {
            $val = $search['value'];
            $records["data"]->where(function ($query) use ($val) {
                $query->SearchVideoSeriesTitle($val)
                    ->SearchSettingName($val)
                    ->SearchSettingStatus($val);
            });

            // No of record after filtering
            $iTotalFiltered = $records["data"]->where(function ($query) use ($val) {
                $query->SearchVideoSeriesTitle($val)
                    ->SearchSettingName($val)
                    ->SearchSettingStatus($val);
            })->count();
        }

        //order by
        foreach ($order as $o) {
            $records["data"] = $records["data"]->orderBy($columns[$o['column']], $o['dir']);
        }

        // Get Record
        $records["data"] = $records["data"]->take($iDisplayLength)->offset($iDisplayStart)->get([
            'settings.id',
            'settings.title',
            'settings.descr',
            'settings.slug',
            'settings.value',
            'settings.status',
            'e_video_book.title AS video_series_title',
        ]);


        if (!empty($records["data"])) {
            foreach ($records["data"] as $key => $_records) {
                $id = UrlService::base64UrlEncode($_records->id);
                $edit = route('setting.edit', $id);
                if ($_records->status == "active") {
                    $records["data"][$key]->status = '<a href="javascript:;" title="Make Inactive" class="btn-status-setting" data-id="' . $id . '"> ' . $_records->status . '</a>';
                } else {
                    $records["data"][$key]->status = '<a href="javascript:;" title="Make Active" class="btn-status-setting" data-id="' . $id . '"> ' . $_records->status . '</a>';
                }
                $records["data"][$key]['action'] = "&emsp;<a href='{$edit}' title='Edit Setting' ><span class='menu-icon icon-pencil'></span></a>";
            }
        }
        $records["draw"] = $sEcho;
        $records["recordsTotal"] = $iTotalRecords;
        $records["recordsFiltered"] = $iTotalFiltered;
        return Response::json($records);
    }

    /**
     * Update controller
     * @param $id = setings id
     */
    public function update($id)
    {
        $id = UrlService::base64UrlDecode($id);
        $setting= Setting::findOrFail($id);
        if ($setting === null) {
            return Redirect::to("/admin/setting")->with('error', trans('admin-message.SETTING_NOT_EXIST'));
        }
        $status = Config::get('constant.STATUS');

        // Get Active Video Series
        $videoSeries = VideoBook::getActiveVideoBook();
        return view('admin.setting.add', compact('setting', 'status','videoSeries'));
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
            // Rule Validation - Start
            $rule = [
            	'video_series_id' =>'required',
                'title' => 'required|max:100',
                'slug' => 'nullable',//max:100|unique:settings,slug,',Rule::unique('settings','slug')->ignore($request->id),
                // 'value' => 'required',
                'status' => ['required', Rule::in([Config::get('constant.ACTIVE_FLAG'), Config::get('constant.INACTIVE_FLAG')])],
            ];

            $validator = Validator::make($request->all(), $rule);

            if ($validator->fails()) {
                return Redirect::back()->withInput($request->all())->withErrors($validator);
            }
            // Rule Validation - End

            // Decrypt Id
            $id = UrlService::base64UrlDecode($request->id);

            // Get Setting
            $setting = Setting::find($id);
            $postData = $request->only('title', 'slug' , 'status');
          
            if (isset($request->id) && UrlService::base64UrlDecode($request->id) > 0) {
            	$postData['value'] = $request->get('video_series_id');
                $setting->update($postData);
                DB::commit();
                return Redirect::to("admin/settings")->with('success', trans('admin-message.SETTING_UPDATED_SUCCESSFULLY_MESSAGE'));
            } 

        } catch (\Exception $e) {
            DB::rollback();
            Log::error(strtr(trans('log-messages.SETTING_EDIT_ERROR_MESSAGE'), [
                '<Message>' => $e->getMessage(),
            ]));
            return Redirect::to("admin/settings")->with('error', trans('admin-message.DEFAULT_ERROR_MESSAGE'));
        }
    }
}
