<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\UserResource;
use Illuminate\Validation\Rule;
use Illuminate\Http\Request;
use App\Models\User;
use App\Models\VideoCategory;
use App\Models\UserDeviceToken;
use App\Services\ImageUpload;
use DB;
use Hash;
use JWTAuth;
use Log;
use Response;
use Storage;
use Stripe\Account;
use Stripe\Stripe;
use Validator;
use Config;

class UserController extends Controller
{

    public function __construct()
    {
        $this->userOriginalImagePath = Config::get('constant.USER_ORIGINAL_PHOTO_UPLOAD_PATH');
        $this->userThumbImagePath = Config::get('constant.USER_THUMB_PHOTO_UPLOAD_PATH');
        $this->userThumbImageHeight = Config::get('constant.USER_THUMB_PHOTO_HEIGHT');
        $this->userThumbImageWidth = Config::get('constant.USER_THUMB_PHOTO_WIDTH');
    }
    /**
     * Save User Video Category
     *
     * @param Request $request
     * @return json response from server
     * @throws Exception If there was a any exeption error
     * @post("user-video-category/save")
     * @Parameters({
     *      @Parameter("categories", description="categories id", type="integer (Query)"),
     * })
     * @Transaction({
     *      @Request( {} ),
     *      @Response( {"status": 0,"message": "error message"} )
     *      @Response( {"status": 1,"message": "Success","data": []} )
     * })
     */
    public function saveCategoryPreference(Request $request)
    {
        try {
            // Rule Validation - Start
            $rule = [
                'categories' => "array",
            ];

            $validator = Validator::make($request->all(), $rule);

            if ($validator->fails()) {
                return response()->json([
                    'status' => 0,
                    'message' => $validator->messages()->all()[0],
                ]);
            }
            // Rule Validation - End

            DB::beginTransaction();
            // Loging user id
            $user = $request->user();
            // get request categories detach
            $user->videoCategories()->detach();
            if (!empty($request->categories)){
                foreach ($request->get('categories') as $category) {
                    if (VideoCategory::where('id', $category)->exists()) {
                        // get request categories attach
                        $user->videoCategories()->attach($category);
                    }
                }
            }

            DB::commit();
            // Get success response
            return response()->json([
                'status' => 1,
                'message' => trans('api-message.USER_VIDEO_CATEGORY_SUCCESS_MESSAGE'),
            ]);

        } catch (\Exception $e) {
            DB::rollback();
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
     * get user Profile
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function show(Request $request)
    {
        try {
            // Get Login user
            $user = $request->user();
            if (!empty($user->stripe_account_id)){
                // set stripe key
                Stripe::setApiKey(Config::get('services.stripe.secret'));
                $account = Account::retrieve($user->stripe_account_id);
                $user['card'] = $account['external_accounts']['data'];
            }

            // All good so return the response
            return response()->json([
                'status' => 1,
                'message' => trans('api-message.PROFILE_FETCHED_SUCCESSFULLY'),
                'data' => new UserResource($user),
            ]);

        } catch (\Exception $e) {
            // Error log
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
     * update Profile image
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function update(Request $request)
    {
        // Start DB transaction
        DB::beginTransaction();
        try {

            // Rule Validation - Start
            $rule = [
                'profile_image' => 'required|mimes:png,jpeg,jpg',
            ];

            $validator = Validator::make($request->all(), $rule);
            if ($validator->fails()) {
                return response()->json([
                    'status' => 0,
                    'message' => $validator->messages()->all()[0],
                ], 200);
            }
            // Rule Validation - End

            // Get Login user
            $user = $request->user();

            // Get previous  prifile image
            $previousImage = $user->photo;

            // Upload user profile picture
            if (!empty($request->file('profile_image')) && $request->file('profile_image')->isValid()) {
                /**
                 * @dev notes:
                 * originalPath & thumbPath these two path MUST be start with public folder otherwise file will not saved.
                 */
                $params = [
                    'originalPath' => $this->userOriginalImagePath,
                    'thumbPath' => $this->userThumbImagePath,
                    'thumbHeight' => $this->userThumbImageHeight,
                    'thumbWidth' => $this->userThumbImageWidth,
                    'previousImage' => $previousImage,
                ];

                $userPhoto = ImageUpload::uploadWithThumbImage($request->file('profile_image'), $params);
                if ($userPhoto === false) {
                    DB::rollback();
                    return response()->json([
                        'status' => 0,
                        'message' => trans('api-message.USER_IMAGE_UPLOAD_ERROR_MSG'),
                    ]);
                }
                // Update user image
                $user->photo = $userPhoto['imageName'];
            }
            
            $user->save();

            // Commit DB changes
            DB::commit();

            // All good so return the response
            return response()->json([
                'status' => 1,
                'message' => trans('api-message.USER_UPDATE_SUCCESSFULLY'),
                'data' => new UserResource($user),
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
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
     * Change password 
     *
     * @param $token
     * @return \Illuminate\Contracts\View\Factory|\Illuminate\Http\JsonResponse|\Illuminate\View\View
     */
    public function changePassword(Request $request)
    {
        // Start DB trasaction
        DB::beginTransaction();
        try {

            // Rule Validation Start
            $rule = [
                'current_password' => 'required|min:6|max:30',
                'new_password' => 'required|min:6|max:30',

            ];

            $validator = Validator::make($request->all(), $rule);
            if ($validator->fails()) {
                return response()->json([
                    'status' => 0,
                    'message' => $validator->messages()->all()[0],
                ], 200);
            }
            // Rule Validation Ends

            $currentPassword = $request->current_password;

            // get Login user
            $user = $request->user();

            if (!Hash::check($currentPassword, $user->password)) {
                return response()->json([
                    'status' => 0,
                    'message' => trans('api-message.CURRENT_PASSWORD_NOT_MATCH'),
                ], 200);

            }
            $user->password = $request->get('new_password');
            $user->save();

            // Commit DB changes
            DB::commit();

            // All good so return Response
            return response()->json([
                'status' => 1,
                'message' => trans('api-message.PASSWORD_CHANGE_SUCESSFULLY'),
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            // log message
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
     * update Profile Details
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function updateProfileDetail(Request $request)
    {
        // Start DB transaction
        DB::beginTransaction();
        try {

            // Rule Validation - Start
            $rule = [
                'name' => 'required|max:100',
                'email' => 'required|email',
                'birth_date'=> 'required|date|date_format:Y-m-d',
                'bio' =>'max:250',
            ];

            $validator = Validator::make($request->all(), $rule);
            if ($validator->fails()) {
                return response()->json([
                    'status' => 0,
                    'message' => $validator->messages()->all()[0],
                ], 200);
            }
            // Rule Validation - End

            // Get Login user
            $user = $request->user();

            $user->full_name = $request->name;
            $user->email = $request->email;
            $user->birth_date = $request->birth_date;
            $user->bio = $request->bio;

            $user->save();

            // Commit DB
            DB::commit();

            // All good so return the response
            return response()->json([
                'status' => 1,
                'message' => trans('api-message.USER_UPDATE_SUCCESSFULLY'),
                'data' => new UserResource($user),
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
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
     * Save Device token 
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function saveDeviceToken(Request $request)
    {
        // Start DB transaction
        DB::beginTransaction();
        try {

            // Rule Validation - Start
            $rule = [
                'device_id'=>'required',
                'device_token' => 'required',
                'device_type' => ['required', Rule::in([Config::get('constant.ANDROID'), Config::get('constant.IPHONE'),Config::get('constant.WEB')])],
            ];

            $validator = Validator::make($request->all(), $rule);
            if ($validator->fails()) {
                return response()->json([
                    'status' => 0,
                    'message' => $validator->messages()->all()[0],
                ]);
            }
            // Rule Validation - End

            // Get Login user
            $user = $request->user();

            $userDeviceToken = UserDeviceToken::where('user_id',$user->id)->where('device_id',$request->device_id)->where('device_type',$request->device_type)->first();

            if(!empty($userDeviceToken))
            {
                $userDeviceToken['device_token'] = $request->device_token;
                $userDeviceToken->save();

                // Commit DB
                DB::commit();
            }else{
                $userDeviceToken = new UserDeviceToken();

                $userDeviceToken->user_id = $user->id;
                $userDeviceToken->device_id =$request->device_id;
                $userDeviceToken->device_token =$request->device_token;
                $userDeviceToken->device_type =$request->device_type;

                $userDeviceToken->save();

                // Commit DB
                DB::commit();


            }
            // All good so return the response
            return response()->json([
                'status' => 1,
                'message' => trans('api-message.DEVICE_TOKEN_SAVE_SUCCESSFULLY'),
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
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
