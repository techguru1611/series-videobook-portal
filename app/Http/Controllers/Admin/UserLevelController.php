<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\UserLevel;
use App\Services\UrlService;
use Config;
use DB;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Log;
use Redirect;
use Response;
use Validator;

class UserLevelController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        return view('admin.user-level.list');
    }

    /**
     * [listAjax List UserLevel]
     * @param  [type]       [description]
     * @return [json]       [list of UserLevel]
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
                    $this->deleteUserLevel($_idArray);
                }
                $records["message"] = trans('admin-message.USER_LEVEL_DELETED_SUCCESSFULLY_MESSAGE');
            }
            if ($action == 'status') {
                foreach ($idArray as $_idArray) {
                    $_idArray = UrlService::base64UrlDecode($_idArray);
                    UserLevel::updateUserLevelStatus($_idArray);
                }
                $records["message"] = trans('admin-message.USER_LEVEL_STATUS_UPDATED_SUCCESS_MESSAGE');
            }
        }

        $columns = array(
            0 => 'title',
            1 => 'purchase',
            2 => 'purchased_video_length',
            3 => 'status',
        );
        $order = $request->order;
        $search = $request->search;
        $records["data"] = array();

        // Getting records from the UserLevel table
        $iTotalRecords = UserLevel::getUserLevelCount();
        $iTotalFiltered = $iTotalRecords;
        $iDisplayLength = intval($request->length) <= 0 ? $iTotalRecords : intval($request->length);
        $iDisplayStart = intval($request->start);
        $sEcho = intval($request->draw);

        $records["data"] = UserLevel::where('user_level.status', '<>', Config::get('constant.DELETED_FLAG'));

        if (!empty($search['value'])) {
            $val = $search['value'];
            $records["data"]->where(function ($query) use ($val) {
                $query->SearchUserLevelTitle($val)
                    ->SearchUserLevelPurchase($val)
                    ->SearchUserLevelPurchasedVideoLength($val)
                    ->SearchUserLevelStatus($val);
            });

            // No of record after filtering
            $iTotalFiltered = $records["data"]->where(function ($query) use ($val) {
                $query->SearchUserLevelTitle($val)
                    ->SearchUserLevelPurchase($val)
                    ->SearchUserLevelPurchasedVideoLength($val)
                    ->SearchUserLevelStatus($val);
            })->count();
        }

        //order by
        foreach ($order as $o) {
            $records["data"] = $records["data"]->orderBy($columns[$o['column']], $o['dir']);
        }

        // Get Record
        $records["data"] = $records["data"]->take($iDisplayLength)->offset($iDisplayStart)->get([
            'user_level.id',
            'user_level.title',
            'user_level.purchase',
            'user_level.purchased_video_length',
            'user_level.status',
        ]);

        if (!empty($records["data"])) {
            foreach ($records["data"] as $key => $_records) {
                $id = UrlService::base64UrlEncode($_records->id);
                $edit = route('user-level.edit', $id);
                if ($_records->status == "active") {
                    $records["data"][$key]->status = '<a href="javascript:;" title="Make Inactive" class="btn-status-user-level" data-id="' . $id . '"> ' . $_records->status . '</a>';
                } else {
                    $records["data"][$key]->status = '<a href="javascript:;" title="Make Active" class="btn-status-user-level" data-id="' . $id . '"> ' . $_records->status . '</a>';
                }
                $records["data"][$key]['action'] = "&emsp;<a href='{$edit}' title='Edit User Level' ><span class='menu-icon icon-pencil'></span></a>
                                                    &emsp;<a href='javascript:;' data-id='" . $id . "' class='btn-delete-user-level' title='Delete User Level' ><span class='menu-icon icon-trash'></span></a>";
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
     * @param $id = UserLevel id
     */
    public function update($id)
    {
        $id = UrlService::base64UrlDecode($id);
        $userLevel = UserLevel::find($id);
        if ($userLevel === null) {
            return Redirect::to("/admin/user-levels")->with('error', trans('admin-message.USER_LEVEL_NOT_EXIST'));
        }
        return $this->_update($userLevel);
    }

    /**
     * Create / Update controller
     * @param $user = UserLevel object
     */
    private function _update($userLevel = null)
    {
        if ($userLevel == null) {
            $userLevel = new UserLevel();
        }
        $status = Config::get('constant.STATUS');
        return view('admin.user-level.add', compact('userLevel', 'status'));
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
                'title' => 'required|max:100', // |regex:/^(?![0-9]*$)[a-zA-Z0-9 ]+$/
                'purchase' => ['required', 'regex:/^[0-9]+(\.[0-9][0-9]?)?$/'],
                'purchased_video_length' => ['required', 'regex:/\d/'],
                'status' => ['required', Rule::in([Config::get('constant.ACTIVE_FLAG'), Config::get('constant.INACTIVE_FLAG')])],
            ];

            $validator = Validator::make($request->all(), $rule);

            // $this->validate(request(), $rule);
            if ($validator->fails()) {
                return Redirect::back()->withInput($request->all())->withErrors($validator);
            }
            // Rule Validation - End

            DB::beginTransaction();

            // Decrypt Id
            $id = UrlService::base64UrlDecode($request->id);

            // Get UserLevel
            $userLevel = UserLevel::find($id);

            $postData = $request->only('title', 'purchase', 'purchased_video_length', 'status');

            if (isset($request->id) && UrlService::base64UrlDecode($request->id) > 0) {
                $userLevel->update($postData);
                DB::commit();
                return Redirect::to("admin/user-levels")->with('success', trans('admin-message.USER_LEVEL_UPDATED_SUCCESSFULLY_MESSAGE'));
            } else {
                $userLevel = new UserLevel($postData);
                $userLevel->save();
                DB::commit();
                return Redirect::to("admin/user-levels")->with('success', trans('admin-message.USER_LEVEL_CREATED_SUCCESSFULLY_MESSAGE'));
            }
        } catch (\Exception $e) {
            DB::rollback();
            Log::error(strtr(trans('log-messages.USER_LEVEL_ADD_EDIT_ERROR_MESSAGE'), [
                '<Message>' => $e->getMessage(),
            ]));
            return Redirect::to("admin/user-levels")->with('error', trans('admin-message.DEFAULT_ERROR_MESSAGE'));
        }
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return Boolean
     */
    public function deleteUserLevel($id)
    {
        try {
            $userLevel = UserLevel::findOrFail($id);

            // If user not found
            if ($userLevel === null) {
                Log::error(strtr(trans('log-messages.USER_LEVEL_DELETE_ERROR_MESSAGE'), [
                    '<Message>' => trans('admin-message.USER_LEVEL_YOU_WANT_TO_DELETE_IS_NOT_FOUND'),
                ]));
                return 0;
            }
            $userLevel->delete();
            return 1;
        } catch (\Exception $e) {
            Log::error(strtr(trans('log-messages.USER_LEVEL_DELETE_ERROR_MESSAGE'), [
                '<Message>' => $e->getMessage(),
            ]));
            return 0;
        }
    }
}
