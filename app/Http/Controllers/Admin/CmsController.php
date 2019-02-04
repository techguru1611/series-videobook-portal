<?php

namespace App\Http\Controllers\Admin;

use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use App\Http\Controllers\Controller;
use App\Services\UrlService;
use App\Models\Cms;
use Config;
use DB;
use Log;
use Redirect;
use Response;
use Validator;

class CmsController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        return view('admin.cms.list');
    }

    /**
     * [listAjax List Cms]
     * @param  [type]   [description]
     * @return [json]   [list of Cms]
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
                    $this->deleteCms($_idArray);
                }
                $records["message"] = trans('admin-message.CMS_DELETED_SUCCESSFULLY_MESSAGE');
            }
            if ($action == 'status') {
                foreach ($idArray as $_idArray) {
                    $_idArray = UrlService::base64UrlDecode($_idArray);
                    Cms::updateCmsStatus($_idArray);
                }
                $records["message"] = trans('admin-message.CMS_STATUS_UPDATED_SUCCESS_MESSAGE');
            }
        }
        $columns = array(
            0 => 'name',
            1 => 'slug',
            2 => 'status',
        );
        $order = $request->order;
        $search = $request->search;
        $records["data"] = array();

        // Getting records from the Cms table
        $iTotalRecords = Cms::getCmsCount();
        $iTotalFiltered = $iTotalRecords;
        $iDisplayLength = intval($request->length) <= 0 ? $iTotalRecords : intval($request->length);
        $iDisplayStart = intval($request->start);
        $sEcho = intval($request->draw);

        $records["data"] = Cms::where('status', '<>', Config::get('constant.DELETED_FLAG'));

        if (!empty($search['value'])) {
            $val = $search['value'];
            $records["data"]->where(function ($query) use ($val) {
                $query->SearchCmsName($val)
                    ->SearchCmsStatus($val);
            });

            // No of record after filtering
            $iTotalFiltered = $records["data"]->where(function ($query) use ($val) {
                $query->SearchCmsName($val)
                    ->SearchCmsStatus($val);
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
                $edit = route('cms.edit', $id);
                if ($_records->status == "active") {
                    $records["data"][$key]->status = '<a href="javascript:;" title="Make Inactive" class="btn-status-cms" data-id="' . $id . '"> ' . $_records->status . '</a>';
                } else {
                    $records["data"][$key]->status = '<a href="javascript:;" title="Make Active" class="btn-status-cms" data-id="' . $id . '"> ' . $_records->status . '</a>';
                }
                $records["data"][$key]['action'] = "&emsp;<a href='{$edit}' title='Edit Cms' ><span class='menu-icon icon-pencil'></span></a>&emsp;<a href='javascript:;' data-id='" . $id . "' class='btn-delete-cms' title='Delete Cms' ><span class='menu-icon icon-trash'></span></a>";
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
     * @param $id = Cms id
     */
    public function update($id)
    {
        $id = UrlService::base64UrlDecode($id);
        $cms = Cms::findOrFail($id);
        if ($cms === null) {
            return Redirect::to("/admin/cms")->with('error', trans('admin-message.CMS_NOT_EXIST'));
        }
        return $this->_update($cms);
    }

    /**
     * Create / Update controller
     * @param $cms = Cms object
     */
    private function _update($cms = null)
    {
        if ($cms == null) {
            $cms = new Cms();
        }
        $status = Config::get('constant.STATUS');
        return view('admin.cms.add', compact('cms', 'status'));
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
                'name' => 'required|max:100',
                'slug' => 'max:100|unique:cms,slug,',Rule::unique('cms','slug')->ignore($request->id),
                'value' => 'required',
                'status' => ['required', Rule::in([Config::get('constant.ACTIVE_FLAG'), Config::get('constant.INACTIVE_FLAG')])],
            ];

            $validator = Validator::make($request->all(), $rule);

            if ($validator->fails()) {
                return Redirect::back()->withInput($request->all())->withErrors($validator);
            }
            // Rule Validation - End

            // Decrypt Id
            $id = UrlService::base64UrlDecode($request->id);

            // Get Cms
            $cms = Cms::find($id);
            $postData = $request->only('name', 'slug' ,'value', 'status');
            DB::beginTransaction();

            if (isset($request->id) && UrlService::base64UrlDecode($request->id) > 0) {
                $cms->update($postData);
                DB::commit();
                return Redirect::to("admin/cms")->with('success', trans('admin-message.CMS_UPDATED_SUCCESSFULLY_MESSAGE'));
            } else {
                $cms = new Cms($postData);
                $cms->save();
                DB::commit();
                return Redirect::to("admin/cms")->with('success', trans('admin-message.CMS_CREATED_SUCCESSFULLY_MESSAGE'));
            }
        } catch (\Exception $e) {
            DB::rollback();
            Log::error(strtr(trans('log-messages.CMS_ADD_EDIT_ERROR_MESSAGE'), [
                '<Message>' => $e->getMessage(),
            ]));
            return Redirect::to("admin/cms")->with('error', trans('admin-message.DEFAULT_ERROR_MESSAGE'));
        }
    }

    /**
     * Remove the specified resource from storage.
     * @param  int  $id
     * @return Boolean
     */
    public function deleteCms($id)
    {
        try {
            $cms = Cms::findOrFail($id);
            // If cms not found
            if ($cms === null) {
                Log::error(strtr(trans('log-messages.CMS_DELETE_ERROR_MESSAGE'), [
                    '<Message>' => trans('admin-message.CMS_NOT_EXIST'),
                ]));
                return 0;
            }
            $cms->delete();
            return 1;
        } catch (\Exception $e) {
            Log::error(strtr(trans('log-messages.CMS_DELETE_ERROR_MESSAGE'), [
                '<Message>' => $e->getMessage(),
            ]));
            return 0;
        }
    }
}
