<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Mail\Register;
use App\Models\Role;
use App\Models\User;
use App\Services\ImageUpload;
use App\Services\UrlService;
use Carbon\Carbon;
use Config;
use DB;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use Illuminate\Validation\Rule;
use Log;
use Redirect;
use Response;
use Validator;
use Storage;

class UserController extends Controller
{
    public function __construct(Register $registrationMail)
    {
        $this->userOriginalImagePath = Config::get('constant.USER_ORIGINAL_PHOTO_UPLOAD_PATH');
        $this->userThumbImagePath = Config::get('constant.USER_THUMB_PHOTO_UPLOAD_PATH');
        $this->userThumbImageHeight = Config::get('constant.USER_THUMB_PHOTO_HEIGHT');
        $this->userThumbImageWidth = Config::get('constant.USER_THUMB_PHOTO_WIDTH');

        $this->tokenExpireHours = Config::get('constant.TOKEN_EXPIRE_HOURS');
        $this->registrationMail = $registrationMail;
    }

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        return view('admin.user.list');
    }

    /**
     * [listAjax List User]
     * @param  [type]       [description]
     * @return [json]       [list of User]
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
                    $this->deleteUser($_idArray);
                }
                $records["message"] = trans('admin-message.USER_DELETED_SUCCESSFULLY_MESSAGE');
            }
            if ($action == 'status') {
                foreach ($idArray as $_idArray) {
                    $_idArray = UrlService::base64UrlDecode($_idArray);
                    User::updateUserStatus($_idArray);
                }
                $records["message"] = trans('admin-message.USER_STATUS_UPDATED_SUCCESS_MESSAGE');
            }
        }

        $columns = array(
            0 => 'full_name',
            1 => 'username',
            2 => 'email',
            3 => 'phone_no',
            4 => 'gender',
            5 => 'photo',
            6 => 'title',
            7 => 'status',
        );
        $order = $request->order;
        $search = $request->search;
        $records["data"] = array();

        // Getting records from the User table
        $iTotalRecords = User::getUserCount();
        $iTotalFiltered = $iTotalRecords;
        $iDisplayLength = intval($request->length) <= 0 ? $iTotalRecords : intval($request->length);
        $iDisplayStart = intval($request->start);
        $sEcho = intval($request->draw);

        $records["data"] = User::leftJoin('user_level', 'users.user_level_id', '=', 'user_level.id')
            ->where('users.id', '<>', $request->user()->id)
            ->where('users.status', '<>', Config::get('constant.DELETED_FLAG'));

        if (!empty($search['value'])) {
            $val = $search['value'];
            $records["data"]->where(function ($query) use ($val) {
                $query->SearchUserFullNameName($val)
                    ->SearchUserName($val)
                    ->SearchEmail($val)
                    ->SearchPhoneNo($val)
                    ->SearchGender($val)
                    ->SearchUserLevel($val)
                    ->SearchStatus($val);
            });

            // No of record after filtering
            $iTotalFiltered = $records["data"]->where(function ($query) use ($val) {
                $query->SearchUserFullNameName($val)
                    ->SearchUserName($val)
                    ->SearchEmail($val)
                    ->SearchPhoneNo($val)
                    ->SearchGender($val)
                    ->SearchUserLevel($val)
                    ->SearchStatus($val);
            })->count();
        }

        //order by
        foreach ($order as $o) {
            $records["data"] = $records["data"]->orderBy($columns[$o['column']], $o['dir']);
        }

        // Get Record
        $records["data"] = $records["data"]->take($iDisplayLength)->offset($iDisplayStart)->get([
            'users.id',
            'users.full_name',
            'users.username',
            'users.email',
            DB::raw('CONCAT(country_code, " ", phone_no) AS phone_no'),
            'users.gender',
            'users.photo',
            'users.status',
            'user_level.title',
        ]);

        if (!empty($records["data"])) {
            foreach ($records["data"] as $key => $_records) {
                $id = UrlService::base64UrlEncode($_records->id);
                $edit = route('user.edit', $id);
                if ($_records->status == "active") {
                    $records["data"][$key]->status = '<a href="javascript:;" title="Make Inactive" class="btn-status-user" data-id="' . $id . '"> ' . $_records->status . '</a>';
                } else {
                    $records["data"][$key]->status = '<a href="javascript:;" title="Make Active" class="btn-status-user" data-id="' . $id . '"> ' . $_records->status . '</a>';
                }
                $image = !empty($_records->photo) ? Storage::disk(env('FILESYSTEM_DRIVER'))->url($this->userOriginalImagePath . $_records->photo)  : asset('images/default_user_profile.png');
                $records["data"][$key]['photo'] = '<img src ='. $image . ' height=50px width=50px>';
                $records["data"][$key]['action'] = "&emsp;<a href='{$edit}' title='Edit User' ><span class='menu-icon icon-pencil'></span></a>
                                                    &emsp;<a href='javascript:;' data-id='" . $id . "' class='btn-delete-user' title='Delete User' ><span class='menu-icon icon-trash'></span></a>";
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
     * @param $id = User id
     */
    public function update($id)
    {
        $id = UrlService::base64UrlDecode($id);
        $user = User::find($id);
        if ($user === null) {
            return Redirect::to("/admin/users")->with('error', trans('admin-message.USER_NOT_EXIST'));
        }
        return $this->_update($user);
    }

    /**
     * Create / Update controller
     * @param $user = User object
     */
    private function _update($user = null)
    {
        if ($user == null) {
            $user = new User();
        }
        $status = Config::get('constant.STATUS');
        return view('admin.user.add', compact('user', 'status'));
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
                'full_name' => 'required|max:100', // |regex:/^(?![0-9]*$)[a-zA-Z0-9 ]+$/
                //'username' => ['required', Rule::unique('users', 'username')->ignore(UrlService::base64UrlDecode($request->id)), 'max:50'],
                //'email' => ['required', 'email', Rule::unique('users', 'email')->ignore(UrlService::base64UrlDecode($request->id)), 'max:100'],
                'username' => 'required|unique:users,username,'. UrlService::base64UrlDecode($request->id) .',id,deleted_at,NULL|max:50',
                'email' => 'required|email|unique:users,email,'. UrlService::base64UrlDecode($request->id) .',id,deleted_at,NULL|max:100',
                'country_code' => 'required|regex:/(\+\d{1,3})/',
                'phone_no' => 'required|regex:/^\d{10,15}$/',
                'gender' => ['required', Rule::in([Config::get('constant.MALE'), Config::get('constant.FEMALE'), Config::get('constant.OTHER')])],
                'birth_date' => 'required|date|date_format:Y-m-d|before:tomorrow',
                'photo' => 'image',
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

            // Get User
            $user = User::find($id);

            $postData = $request->only('full_name', 'username', 'email', 'country_code', 'phone_no', 'gender', 'birth_date', 'status');

            // Upload User Photo
            if (!empty($request->file('photo')) && $request->file('photo')->isValid()) {
                $params = [
                    'originalPath' => $this->userOriginalImagePath,
                    'thumbPath' => $this->userThumbImagePath,
                    'thumbHeight' => $this->userThumbImageHeight,
                    'thumbWidth' => $this->userThumbImageWidth,
                    'previousImage' => $request->hidden_photo,
                ];
                $userPhoto = ImageUpload::uploadWithThumbImage($request->file('photo'), $params);
                if ($userPhoto === false) {
                    DB::rollback();

                    // Log the error
                    Log::error(strtr(trans('log-messages.USER_IMAGE_UPLOAD_ERROR_MESSAGE'), [
                        '<Message>' => $e->getMessage(),
                    ]));

                    return Redirect::back()->withInput($request->all())->with('error', trans('admin-message.USER_IMAGE_UPLOAD_ERROR_MSG'));
                }
                $postData['photo'] = $userPhoto['imageName'];
            }

            if (isset($request->id) && UrlService::base64UrlDecode($request->id) > 0) {
                $user->update($postData);
                DB::commit();
                return Redirect::to("admin/users")->with('success', trans('admin-message.USER_UPDATED_SUCCESSFULLY_MESSAGE'));
            } else {
                $password = str_random(8);
                $postData['password'] = $password; // Set random string for password
                $postData['is_verified'] = 1;
                $user = User::withTrashed()->where('email', $request->email)->first();
                if (!$user){
                    $user = new User($postData);
                    $user->save();
                    $role = Role::where('slug', Config::get('constant.NORMAL_USER_SLUG'))->first();

                    $user->roles()->save($role);
                } else{
                    $postData['deleted_at'] = null;
                    $postData['is_verified'] = 1;
                    $user->update($postData);
                }

                // send password as mail
                try{
                    $data = [
                        'email' =>$user->email,
                        'full_name' => $user->full_name,
                        'password' => $password
                    ];
                    $this->registrationMail->setUser($data);
                    Mail::to($data['email'])->send($this->registrationMail);
                } catch (\Exception $e){
                    DB::rollback();
                    Log::error(strtr(trans('log-messages.USER_ADD_EDIT_ERROR_MESSAGE'), [
                        '<Message>' => $e->getMessage(),
                    ]));
                    return Redirect::to("admin/users")->with('error', trans('admin-message.DEFAULT_ERROR_MESSAGE'));
                }

                DB::commit();
                return Redirect::to("admin/users")->with('success', trans('admin-message.USER_CREATED_SUCCESSFULLY_MESSAGE'));
            }
        } catch (\Exception $e) {
            DB::rollback();
            Log::error(strtr(trans('log-messages.USER_ADD_EDIT_ERROR_MESSAGE'), [
                '<Message>' => $e->getMessage(),
            ]));
            return Redirect::to("admin/users")->with('error', trans('admin-message.DEFAULT_ERROR_MESSAGE'));
        }
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return Boolean
     */
    public function deleteUser($id)
    {
        try {
            $user = User::findOrFail($id);

            // If user not found
            if ($user === null) {
                Log::error(strtr(trans('log-messages.USER_DELETE_ERROR_MESSAGE'), [
                    '<Message>' => trans('admin-message.USER_YOU_WANT_TO_DELETE_IS_NOT_FOUND'),
                ]));
                return 0;
            }
            $user->is_verified = 0;
            $user->save();
            $user->delete();
            return 1;
        } catch (\Exception $e) {
            Log::error(strtr(trans('log-messages.USER_DELETE_ERROR_MESSAGE'), [
                '<Message>' => $e->getMessage(),
            ]));
            return 0;
        }
    }

    /**
     * Activate user account by verifying email
     *
     * @param $token
     * @return \Illuminate\Contracts\View
     */
    public function activate(Request $request, $token)
    {
        try {
            // Get user from given token
            $user = User::where('activation_token', $token)->first();

            // If user not exist
            if (!$user) {
                return view('activate', ['success' => 0, 'name' => '', 'message' => trans('admin-message.INVALID_TOKEN')]);
            }

            // Check that token expire or not
            if (!is_null($user->activation_token_created_at) && Carbon::now()->diffInHours($user->activation_token_created_at) <= $this->tokenExpireHours) {
                // Activate account
                $user->update([
                    'is_verified' => 1,
                    'activation_token' => null,
                    'activation_token_created_at' => null,
                ]);

                return view('activate', ['success' => 1, 'name' => $user->full_name, 'message' => trans('admin-message.ACCOUNT_VERIFIED_SUCCESS_MESSAGE')]);
            } else {
                return view('activate', ['success' => 0, 'name' => $user->full_name, 'message' => trans('admin-message.TOKEN_EXPIRE_ERROR_MESSAGE')]);
            }
        } catch (\Exception $e) {
            // log message
            Log::error(strtr(trans('log-messages.DEFAULT_ERROR_MESSAGE'), [
                '<Message>' => $e->getMessage(),
            ]));
            return view('activate', ['success' => 0, 'name' => '', 'message' => trans('admin-message.DEFAULT_ERROR_MESSAGE')]);
        }
    }
}
