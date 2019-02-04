<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\UserResource;
use App\Mail\ActivationMail;
use App\Mail\Register;
use App\Mail\ResetPasswordMail;
use App\Mail\ForgotPasswordMail;
use App\Models\PasswordReset;
use App\Models\Role;
use App\Models\User;
use App\Models\UserDeviceToken;
use Carbon\Carbon;
use Config;
use DB;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use JWTAuth;
use Log;
use Mail;
use Redirect;
use Validator;

class AuthController extends Controller
{
    const RESET_PASSWORD_TOKEN_LENGTH = 60;
    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct(Register $registrationMail, ActivationMail $activationMail, ResetPasswordMail $resetPasswordMail ,ForgotPasswordMail $forgotPasswordMail)
    {
        $this->objUser = new User();
        $this->tokenExpireHours = Config::get('constant.TOKEN_EXPIRE_HOURS');
        $this->userOriginalImagePath = Config::get('constant.USER_ORIGINAL_PHOTO_UPLOAD_PATH');
        $this->userThumbImagePath = Config::get('constant.USER_THUMB_PHOTO_UPLOAD_PATH');
        $this->userThumbImageHeight = Config::get('constant.USER_THUMB_PHOTO_HEIGHT');
        $this->userThumbImageWidth = Config::get('constant.USER_THUMB_PHOTO_WIDTH');

        $this->registrationMail = $registrationMail;
        $this->activationMail = $activationMail;
        $this->resetPasswordMail = $resetPasswordMail;
        $this->forgotPasswordMail = $forgotPasswordMail;
    }

    /**
     * register a user
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function register(Request $request)
    {
        // Start DB Transaction
        DB::beginTransaction();
        try {
            // Rule Validation - Start
            $rule = [
                'full_name' => 'required|max:100',
                'email' => 'required|email|unique:users,email,NULL,id,deleted_at,NULL,is_verified,1|max:100',
                'password' => 'required|min:6|max:20|confirmed',
                'birth_date' => 'required|date|date_format:Y-m-d',
            ];

            $validator = Validator::make($request->all(), $rule);

            if ($validator->fails()) {
                return response()->json([
                    'status' => 0,
                    'message' => $validator->messages()->all()[0],
                ], 200);
            }
            // Rule Validation - Ends
            $user = User::withTrashed()->where('email',$request->email)->first();

            // Check if user email verification status
            if(isset($user)){
                if ($user->is_verified != 1) {
                    DB::rollBack();
                    return response()->json([
                        'status' => 0,
                        'message' => trans('api-message.USER_ACCOUNT_EMAIL_IS_NOT_VERIFIED'),
                    ], 200);
                }
            }

            // Generate activation code
            $activationOTP = mt_rand(Config::get('constant.START'), Config::get('constant.END'));
            $postData = $request->only('full_name', 'email', 'birth_date', 'password');
            $postData['activation_otp'] = $activationOTP;
            $postData['activation_otp_created_at'] = Carbon::now();

            $user = User::withTrashed()->where('email', $request->email)->first();

            if (!$user){
                // Add user
                $user = new User(array_filter($postData));
                $user->save();

                // Assign role to the user
                $role = Role::findActiveRoleBySlug(Config::get('constant.NORMAL_USER_SLUG'));
                $user->roles()->attach($role);
            } else{
                $postData['deleted_at'] = null;
                $user->update($postData);
            }
            // Send activation mail

            $this->sendActivationMail([
                'activationOTP' => $activationOTP,
                'full_name' => $user->full_name,
                'email' => $user->email,
            ]);

            Log::info(strtr(trans('log-messages.MAIL_SENT'), [
                '<Mail>' => $user->email,
            ]));

            // Commit to DB
            DB::commit();

            // Get new inserted users data
            $user = User::find($user->id);

            // All good so return the response
            return response()->json([
                'status' => 1,
                'message' => trans('api-message.ACTIVATION_MAIL_SENT_SUCCESSFULLY'),
                'data' => new UserResource($user),
            ]);
            
        } catch (\Exception $e) {
            DB::rollBack();
            // Log error message
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
     * login to user
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function login(Request $request)
    {
        try {
            // Rule Validation - Start
            $rule = [
                'email' => 'required|email',
                'password' => 'required',
            ];

            $validator = Validator::make($request->all(), $rule);

            if ($validator->fails()) {
                return response()->json([
                    'status' => 0,
                    'message' => $validator->messages()->all()[0],
                ]);
            }
            // Rule Validation - Ends

            // Get user details
            $user = User::with('videoCategories')->where('email', $request->email)->first();

            if (!$user) {
                return response()->json([
                    'status' => 0,
                    'message' => trans('api-message.USER_NOT_FOUND'),
                ], 200);
            }

            // Authorize user credentials
            $credentials = $request->only(['email', 'password']);

            if (!$token = JWTAuth::attempt($credentials)) {
                Log::error(strtr(trans('log-messages.INVALID_LOGIN'), [
                    '<Email>' => $request->email,
                ]));
                return response()->json([
                    'status' => 0,
                    'message' => trans('api-message.INVALID_LOGIN'),
                ]);
            }

            // If user is inactivated
            if ($user->is_verified == 0) {
                return response()->json([
                    'status' => 1,
                    'message' => trans('api-message.USER_ACCOUNT_EMAIL_NOT_VERIFIED'),
                    'data' => ['isVerified' => false]
                ], 200);
            }

            // If user is inactivated
            if ($user->status == Config::get('constant.INACTIVE_FLAG')) {
                return response()->json([
                    'status' => 0,
                    'message' => trans('api-message.INACTIVE_USER'),
                ], 200);
            }
            
            // All good so return the response
            return response()->json([
                'status' => 1,
                'message' => trans('api-message.LOGIN_SUCCESS'),
                'data' => [
                    'loginToken' => $token,
                    'user' => new UserResource($user),
                ],
            ]);
        } catch (\Exception $e) {
            // Log Error message
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
     * Social Login
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function authenticateSocialLogin(Request $request)
    {
        try {
            // Rule Validation - Start
            $rule = [
                'type' => ['required', Rule::in([Config::get('constant.FB_LOGIN'), Config::get('constant.GOOGLE_LOGIN')])],
                'id_token' => 'required',
                'email' => 'email',
            ];

            $validator = Validator::make($request->all(), $rule);

            if ($validator->fails()) {
                return response()->json([
                    'status' => 0,
                    'message' => $validator->messages()->all()[0],
                ]);
            }
            // Rule Validation - End

            try {
                if ($request->type == Config::get('constant.GOOGLE_LOGIN')) {
                    $response = $this->validateGoogleLogin($request->id_token, $request->header('platform'));
                } else if ($request->type == Config::get('constant.FB_LOGIN')) {
                    $response = $this->validateFacebookLogin($request->id_token);
                }

                // If any error occured while authenticate
                if (isset($response['status']) && $response['status'] == 0) {
                    return response()->json([
                        'status' => $response['status'],
                        'message' => $response['message'],
                    ], $response['code']);
                }
            } catch (\Exception $e) {
                // Log social login error messages
                Log::error(strtr(trans('log-messages.SOCIAL_AUTHENTICATION_ERROR'), [
                    '<Message>' => $e->getMessage(),
                ]));

                return response()->json([
                    'status' => 0,
                    'message' => trans('api-message.SOCIAL_AUTHENTICATION_ERROR_MESSAGE'),
                ]);
            }

            if (isset($request->email) && !empty($request->email) && !empty($response['email'])){
                // Any unauthorize action found
                if ($response['email'] != $request->email) {
                    return response()->json([
                        'status' => 0,
                        'message' => trans('api-message.UNAUTHORIZED_ACTION'),
                    ], 401);
                }

                $user = User::where('email', $request->email)->withTrashed()->first();
            } else{
                $user = null;
            }


            if ($request->type == Config::get('constant.GOOGLE_LOGIN')) {
                $data = [
                    'google_id' => $response['id'],
                ];
            } else if ($request->type == Config::get('constant.FB_LOGIN')) {
                $data = [
                    'facebook_id' => $response['id'],
                ];
            }

            // Start database transaction
            DB::beginTransaction();

            if ($user === null) {
                $data['full_name'] = $response['full_name'];
                $data['email'] = $response['email'];
                $data['password'] = str_random(8);
                $data['status'] = Config::get('constant.ACTIVE_FLAG');
                $data['is_verified'] = 1;

                // Save user data
                $user = new User($data);
                $user->save();

                // Assign role to the user
                $role = Role::findActiveRoleBySlug(Config::get('constant.NORMAL_USER_SLUG'));
                $user->roles()->attach($role);

                // Send registration mail
                if (!empty($response['email'])){
                    $this->sendRegisterMail($data);
                }
            } else {
                // Update user social id
                $data['is_verified'] = 1;
                $data['deleted_at'] = null;
                $user->update($data);
            }

            // Check if user deleted
            /*if ($user->deleted_at != null) {
                DB::rollBack();
                return response()->json([
                    'status' => 0,
                    'message' => trans('api-message.USER_ACCOUNT_DELETED_BY_ADMIN'),
                ], 401);
            }*/

            // Check if user status inactivated
            if ($user->status != Config::get('constant.ACTIVE_FLAG')) {
                DB::rollBack();
                return response()->json([
                    'status' => 0,
                    'message' => trans('api-message.USER_ACCOUNT_INACTIVATED_BY_ADMIN'),
                ], 401);
            }

            // Check if user email verification status
            if ($user->is_verified != 1) {
                DB::rollBack();
                return response()->json([
                    'status' => 0,
                    'message' => trans('api-message.USER_ACCOUNT_EMAIL_NOT_VERIFIED'),
                ], 401);
            }

            // Commit database chnages
            DB::commit();

            // Generate auth token
            $token = JWTAuth::fromUser($user);

            // All good so return the response
            return response()->json([
                'status' => 1,
                'message' => trans('api-message.DEFAULT_SUCESS_MESSAGE'),
                'data' => [
                    'loginToken' => $token,
                    'user' => new UserResource($user),
                ],
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            // Log error messages
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
     * forgot Password
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function reset(Request $request)
    {
        try {

            // Rule Validation - Start
            $rule = [
                'email' => 'required|email',
            ];

            $validator = Validator::make($request->all(), $rule);

            if ($validator->fails()) {
                return response()->json([
                    'status' => 0,
                    'message' => $validator->messages()->all()[0],
                ]);
            }
            // Rule Validation - End

            return $this->doReset($request);
        } catch (\Exception $e) {
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

    public function sendEmailVerificationLink(Request $request)
    {
        try {
            // Rule Validation - Start
            $rule = [
                'email' => 'required|email',
            ];

            $validator = Validator::make($request->all(), $rule);

            if ($validator->fails()) {
                return Redirect::back()->withInput($request->all())->withErrors($validator);
            }
            // Rule Validation - End

            // Get user detail from email
            $user = User::findUserByEmail($request->email);

            if (is_null($user)) {
                return response()->json([
                    'status' => 0,
                    'message' => trans('api-message.USER_NOT_FOUND'),
                ]);
            }

            // Check if user deleted
            if ($user->deleted_at != null) {
                return response()->json([
                    'status' => 0,
                    'message' => trans('api-message.USER_ACCOUNT_DELETED_BY_ADMIN'),
                ]);
            }

            // Check if user status inactivated
            if ($user->status != Config::get('constant.ACTIVE_FLAG')) {
                return response()->json([
                    'status' => 0,
                    'message' => trans('api-message.USER_ACCOUNT_INACTIVATED_BY_ADMIN'),
                ]);
            }

            // If account already verified
            if ($user->is_verified == 1) {
                return response()->json([
                    'status' => 0,
                    'message' => trans('api-message.USER_ACCOUNT_ALREADY_VERIFIED'),
                ]);
            }

            // Send activation mail
            $activationToken = str_random(Config::get('constant.ACTIVATION_TOKEN_LENGTH'));
            $user->update([
                'activation_token' => $activationToken,
                'activation_token_created_at' => Carbon::now(),
            ]);

            $this->sendActivationMail([
                'activationLink' => url('activate/' . $activationToken),
                'full_name' => $user->full_name,
                'email' => $user->email,
            ]);

            Log::info(strtr(trans('log-messages.MAIL_SENT'), [
                '<Mail>' => $user->email,
            ]));

            // All good so return the response
            return response()->json([
                'status' => 1,
                'message' => trans('api-message.ACTIVATION_MAIL_SENT_SUCCESSFULLY'),
            ]);
        } catch (\Exception $e) {
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
     * logout
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
    */
    public function logout(Request $request)
    {
        // Start DB transaction
        DB::beginTransaction();
        try {

            // Rule Validation - Start
            $rule = [
                'device_id' => 'required',
                'device_type' => ['required', Rule::in([Config::get('constant.ANDROID'), Config::get('constant.IPHONE'), Config::get('constant.WEB')])],
            ];

            $validator = Validator::make($request->all(), $rule);
            if ($validator->fails()) {
                return response()->json([
                    'status' => 0,
                    'message' => $validator->messages()->all()[0],
                ]);
            }
            // Rule Validation - End

            // Get bearer token
            $bearerToken = explode(' ', $request->header('Authorization'))[1];

            if (JWTAuth::invalidate($bearerToken)) {
                // Get Login user
                $user = $request->user();

                $userDeviceToken = UserDeviceToken::where('user_id', $user->id)->where('device_id', $request->device_id)->where('device_type', $request->device_type)->first();

                if (!empty($userDeviceToken)) {
                    $userDeviceToken->delete();
                    // Commit DB
                    DB::commit();
                } else {

                    return response()->json([
                        'status' => 0,
                        'message' => trans('api-message.INVALID_OR_BLACKLISTED_TOKEN'),
                    ]);
                }

                // All good so return the response
                return response()->json([
                    'status' => 1,
                    'message' => trans('api-message.LOGOUT_SUCCESSFULLY'),
                ]);
            } else {
                // Return error message when token Unauthorized
                return response()->json([
                    'status' => 0,
                    'message' => trans('api-message.INVALID_AUTHORAZATION_TOKEN'),
                ]);
            }

        } catch (\Exception $e) {
            DB::rollBack();
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
     * Check user, then generate token and send email to user.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return JSON object
     */
    private function doReset(Request $request)
    {
        try {
            // Get User Details
            $user = User::where('email', $request->email)->first();

            // If user does not exist
            if (is_null($user)) {
                return response()->json([
                    'status' => 0,
                    'message' => trans('api-message.USER_NOT_FOUND'),
                ]);
            }

            // If user is deactivated
            if ($user->status == Config::get('constant.INACTIVE_FLAG')) {
                return response()->json([
                    'status' => 0,
                    'message' => trans('api-message.USER_ACCOUNT_INACTIVATED_BY_ADMIN'),
                ]);
            }

            // If user does not verified email
            if (!$user->is_verified) {
                return response()->json([
                    'status' => 0,
                    'message' => trans('api-message.USER_ACCOUNT_EMAIL_NOT_VERIFIED'),
                ]);
            }

            $token = base64_encode(str_random(self::RESET_PASSWORD_TOKEN_LENGTH));

            $this->saveResetToken($request, $token);
            $this->sendResetPasswordMail($request, url('password/reset/' . $token));

            return response()->json([
                'status' => 1,
                'message' => trans('api-message.RESET_PASSWORD_LINK_SENT_SUCCESS_MESSAGE'),

            ]);
        } catch (\Exception $e) {
            // Log error message
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
     * Generate new token and save it to database.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  string  $token
     * @return void
     */
    private function saveResetToken(Request $request, $token)
    {
        PasswordReset::where('email', $request->email)->delete();
        PasswordReset::create([
            'email' => $request->email,
            'token' => $token,
        ]);
    }

    /**
     * Send successful email to new user.
     *
     * @param  [Array]  $data
     * @return void
     */
    private function sendRegisterMail($data)
    {
        $this->registrationMail->setUser($data);
        Mail::to($data['email'])->send($this->registrationMail);
    }

    /**
     * Send activation mail to user
     *
     * @param  [Array]  $data
     * @return void
     */
    private function sendActivationMail($data)
    {
        $this->activationMail->setUser($data);
        Mail::to($data['email'])->send($this->activationMail);
    }

    /**
     * Send password recovery email that contains a secret token to user.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  string  $token
     * @return void
     */
    private function sendResetPasswordMail(Request $request, $resetPasswordLink)
    {
        $this->resetPasswordMail->setResetPasswordLink($resetPasswordLink);
        Mail::to($request->email)->send($this->resetPasswordMail);
    }

    /**
     * Send password recovery email that contains a secret otp to user.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  string  $token
     * @return void
     */
    private function sendForgotPasswordMail(Request $request, $resetOTP)
    {
        $this->forgotPasswordMail->resetOtp($resetOTP);
        Mail::to($request->email)->send($this->forgotPasswordMail);
    }

    /**
     * forgot Password using OTP
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function resetPassword(Request $request)
    {
        // Start DB transaction
        DB::beginTransaction();
        try {
            // Rule Validation - Start
            $rule = [
                'email' => 'required|email',
            ];

            $validator = Validator::make($request->all(), $rule);

            if ($validator->fails()) {
                return response()->json([
                    'status' => 0,
                    'message' => $validator->messages()->all()[0],
                ]);
            }
            // Rule Validation - End

            // Get User Details
            $user = User::where('email', $request->email)->first();

            // If user does not exist
            if (is_null($user)) {
                return response()->json([
                    'status' => 0,
                    'message' => trans('api-message.USER_NOT_FOUND'),
                ]);
            }

            // If user is deactivated
            if ($user->status == Config::get('constant.INACTIVE_FLAG')) {
                return response()->json([
                    'status' => 0,
                    'message' => trans('api-message.USER_ACCOUNT_INACTIVATED_BY_ADMIN'),
                ]);
            }

            // If user does not verified email
            if (!$user->is_verified) {
                return response()->json([
                    'status' => 0,
                    'message' => trans('api-message.USER_ACCOUNT_EMAIL_NOT_VERIFIED'),
                ]);
            }

            // If user role is super Admin
            if ($user->hasRole(Config::get('constant.SUPER_ADMIN_SLUG'))){
                return response()->json([
                    'status' => 0,
                    'message' => trans('api-message.YOU_CANT_PERFORM_THIS_ACTION'),
                ]);
            }

            // Password reset otp send
            $created = new Carbon($user->reset_otp_created_at);
            $now = Carbon::now();
            if(($user->reset_otp_created_at != null || $user->reset_otp_created_at != "" ) && $now->diffInMinutes($created) < Config::get('constant.MINUTES'))
            {
                    $resetoldOTP = $user->reset_otp;
                    //send mail
                    $this->sendForgotPasswordMail($request,$resetoldOTP);

                    // Return response sucsess
                    return response()->json([
                        'status' => 1,
                        'message' => strtr(trans('api-message.RESET_MAIL_SENT'), [
                        '<Mail>' => $user->email,
                        ]),
                    ]); 
            }else{

                    // Generate OTP
                    $resetOTP = mt_rand(Config::get('constant.START'), Config::get('constant.END'));

                    $user->update([
                        'reset_otp' => $resetOTP,
                        'reset_otp_created_at' => Carbon::now(),
                        'reset_otp_is_verified' => Config::get('constant.OTP_VERIFIED_FALSE'),
                    ]);

                    // Commit to DB
                    DB::commit();

                    //send mail
                    $this->sendForgotPasswordMail($request,$resetOTP);

                    // Return response sucsess
                    return response()->json([
                        'status' => 1,
                        'message' => strtr(trans('api-message.RESET_MAIL_SENT'), [
                            '<Mail>' => $user->email,
                        ]),
                    ]);
                }

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
     * forgot Password verify OTP
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function verifyResetOTP(Request $request)
    {
        try {

            // Rule Validation - Start
            $rule = [
                'email' => 'required|email',
                'otp' =>'required|integer',
            ];

            $validator = Validator::make($request->all(), $rule);

            if ($validator->fails()) {
                return response()->json([
                    'status' => 0,
                    'message' => $validator->messages()->all()[0],
                ]);
            }
            // Rule Validation - End

            // Get User Details
            $user = User::where('email', $request->email)->first();

            if(!User::where('email',$request->email)->where('reset_otp',$request->otp)->exists()){
                return response()->json([
                    'status' => 0,
                    'message' => trans('api-message.NO_USER_FOUND_WITH_THIS_EMAIL_AND_OTP'),
                ]);
            }

            // If user does not exist
            if (is_null($user)) {
                return response()->json([
                    'status' => 0,
                    'message' => trans('api-message.USER_NOT_FOUND'),
                ]);
            }

            // If user is deactivated
            if ($user->status == Config::get('constant.INACTIVE_FLAG')) {
                return response()->json([
                    'status' => 0,
                    'message' => trans('api-message.USER_ACCOUNT_INACTIVATED_BY_ADMIN'),
                ]);
            }

            // If user role is super Admin
            if ($user->hasRole(Config::get('constant.SUPER_ADMIN_SLUG'))){
                return response()->json([
                    'status' => 0,
                    'message' => trans('api-message.YOU_CANT_PERFORM_THIS_ACTION'),
                ]);
            }

            // If user does not verified email
            if (!$user->is_verified) {
                return response()->json([
                    'status' => 0,
                    'message' => trans('api-message.USER_ACCOUNT_EMAIL_NOT_VERIFIED'),
                ]);
            }

            // Password reset token expired
            $created = new Carbon($user->reset_otp_created_at);
            $now = Carbon::now();
            if ($now->diffInMinutes($created) > Config::get('constant.RESET_PASSWORD_OTP_EXPIRE_IN_MINUTES')){
                return response()->json([
                    'status' => 0,
                    'message' => trans('api-message.OTP_IS_VALID_FOR_60_MINUTES'),
                ]);
            }

            // Check Otp
            if ($request->otp == $user->reset_otp) {

                // Set is verified to true 
                $user->update([
                        'reset_otp_is_verified' => Config::get('constant.OTP_VERIFIED_TRUE'),
                    ]); 

                return response()->json([
                    'status' => 1,
                    'message' => trans('api-message.OTP_VERIFIED_SUCCESS'),
                    ]);
            }else{
                return response()->json([
                    'status' => 0,
                    'message' => trans('api-message.PLEASE_ENTER_VALID_OTP')
                ]);
            }

            // Return Error Message
            return response()->json([
                'status' => 0,
                'message' => trans('api-message.DEFAULT_ERROR_MESSAGE'),
            ], 500);


        } catch (\Exception $e) {
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
     * Reset Password 
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function resetPasswordUsingOTP(Request $request)
    {
        try {

            // Rule Validation - Start
            $rule = [
                'email' => 'required|email',
                'otp' =>'required|integer',
                'password' => 'required|min:6',
            ];

            $validator = Validator::make($request->all(), $rule);

            if ($validator->fails()) {
                return response()->json([
                    'status' => 0,
                    'message' => $validator->messages()->all()[0],
                ]);
            }
            // Rule Validation - End

            // Get User Details
            $user = User::where('email', $request->email)->first();

            // If user does not exist
            if (is_null($user)) {
                return response()->json([
                    'status' => 0,
                    'message' => trans('api-message.USER_NOT_FOUND'),
                ]);
            }

            // If user is deactivated
            if ($user->status == Config::get('constant.INACTIVE_FLAG')) {
                return response()->json([
                    'status' => 0,
                    'message' => trans('api-message.USER_ACCOUNT_INACTIVATED_BY_ADMIN'),
                ]);
            }

            // If user role is super Admin
            if ($user->hasRole(Config::get('constant.SUPER_ADMIN_SLUG'))){
                return response()->json([
                    'status' => 0,
                    'message' => trans('api-message.YOU_CANT_PERFORM_THIS_ACTION'),
                ]);
            }

            // If user does not verified email
            if (!$user->is_verified) {
                return response()->json([
                    'status' => 0,
                    'message' => trans('api-message.USER_ACCOUNT_EMAIL_NOT_VERIFIED'),
                ]);
            }

            // Password reset token expired
            $created = new Carbon($user->reset_otp_created_at);
            $now = Carbon::now();
            if ($now->diffInMinutes($created) > Config::get('constant.RESET_PASSWORD_OTP_EXPIRE_IN_MINUTES')){
                return response()->json([
                    'status' => 0,
                    'message' => trans('api-message.OTP_IS_VALID_FOR_60_MINUTES'),
                ]);
            }

            // If user does not exist with this email and otp
            if(!User::where('email',$request->email)->where('reset_otp',$request->otp)->where('reset_otp_is_verified',Config::get('constant.OTP_VERIFIED_TRUE'))->exists()){
                    return response()->json([
                        'status' => 0,
                        'message' => trans('api-message.NO_USER_FOUND_WITH_THIS_EMAIL_AND_OTP'),
                    ]);
            }else{

                // Check Otp
                if ($request->otp == $user->reset_otp) {

                    $user->update([
                        'password' => $request->password,
                        'reset_otp' => null,
                        'reset_otp_created_at' => null,
                        'reset_otp_is_verified' => null,
                    ]);

                    return response()->json([
                        'status' => 1,
                        'message' => trans('api-message.PASSWORD_RESET_SUCCESS'),
                    ]);
                }else{
                    return response()->json([
                        'status' => 1,
                        'message' => trans('api-message.PLEASE_ENTER_VALID_OTP'),
                    ]);
                }
            }

            // Return Error Message
            return response()->json([
                'status' => 0,
                'message' => trans('api-message.DEFAULT_ERROR_MESSAGE'),
            ], 500);

        } catch (\Exception $e) {
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


    // Send verification Mail with OTP

    public function sendEmailVerificationOTP(Request $request)
    {
        // Start DB tarnsaction 
        DB::beginTransaction();
        try {
            // Rule Validation - Start
            $rule = [
                'email' => 'required|email',
            ];

            $validator = Validator::make($request->all(), $rule);

            if ($validator->fails()) {
                return response()->json([
                    'status' => 0,
                    'message' => $validator->messages()->all()[0],
                ]);
            }
            // Rule Validation - End

            // Get user detail from email
            $user = User::findUserByEmail($request->email);

            if (is_null($user)) {
                return response()->json([
                    'status' => 0,
                    'message' => trans('api-message.USER_NOT_FOUND'),
                ]);
            }

            // Check if user deleted
            if ($user->deleted_at != null) {
                return response()->json([
                    'status' => 0,
                    'message' => trans('api-message.USER_ACCOUNT_DELETED_BY_ADMIN'),
                ]);
            }

            // Check if user status inactivated
            if ($user->status != Config::get('constant.ACTIVE_FLAG')) {
                return response()->json([
                    'status' => 0,
                    'message' => trans('api-message.USER_ACCOUNT_INACTIVATED_BY_ADMIN'),
                ]);
            }

            // If account already verified
            if ($user->is_verified == 1) {
                return response()->json([
                    'status' => 0,
                    'message' => trans('api-message.USER_ACCOUNT_ALREADY_VERIFIED'),
                ]);
            }

            // Send activation  otp to Mail
            $created = new Carbon($user->activation_otp_created_at);
            $now = Carbon::now();
            if(($user->activation_otp_created_at != null || $user->activation_otp_created_at != "" ) && $now->diffInMinutes($created) < Config::get('constant.MINUTES')) 
            {
                    $activationOTP = $user->activation_otp;
                    
                    //send mail
                    $this->sendActivationMail([
                        'activationOTP' => $activationOTP,
                        'full_name' => $user->full_name,
                        'email' => $user->email,
                    ]);

                    Log::info(strtr(trans('log-messages.MAIL_SENT'), [
                        '<Mail>' => $user->email,
                    ]));

                    // All good so return the response
                    return response()->json([
                        'status' => 1,
                        'message' => trans('api-message.ACTIVATION_MAIL_SENT_SUCCESSFULLY'),
                    ]);
            }else{

                    // Generate  activation OTP
                    $activationOTP = mt_rand(Config::get('constant.START'), Config::get('constant.END'));

                    $user->update([
                        'activation_otp' => $activationOTP,
                        'activation_otp_created_at' => Carbon::now(),
                        'is_verified' => Config::get('constant.ACTIVATION_OTP_VERIFIED_FALSE'),
                    ]);

                    // Commit to DB
                    DB::commit();

                    // Send activation mail

                    $this->sendActivationMail([
                        'activationOTP' => $activationOTP,
                        'full_name' => $user->full_name,
                        'email' => $user->email,
                    ]);

                    Log::info(strtr(trans('log-messages.MAIL_SENT'), [
                        '<Mail>' => $user->email,
                    ]));

                    // All good so return the response
                    return response()->json([
                        'status' => 1,
                        'message' => trans('api-message.ACTIVATION_MAIL_SENT_SUCCESSFULLY'),
                    ]);
            }

        } catch (\Exception $e) {
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
     * Verify activation  OTP
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
    */
    public function verifyActivationOTP(Request $request)
    {
        try {
            // Rule Validation - Start
            $rule = [
                'email' => 'required|email',
                'otp' =>'required|integer',
            ];

            $validator = Validator::make($request->all(), $rule);

            if ($validator->fails()) {
                return response()->json([
                    'status' => 0,
                    'message' => $validator->messages()->all()[0],
                ]);
            }
            // Rule Validation - End

            // Get User Details
            $user = User::where('email', $request->email)->first();

            if(!User::where('email',$request->email)->where('activation_otp',$request->otp)->exists()){
                return response()->json([
                    'status' => 0,
                    'message' => trans('api-message.NO_USER_FOUND_WITH_THIS_OTP'),
                ]);
            }

            // If user does not exist
            if (is_null($user)) {
                return response()->json([
                    'status' => 0,
                    'message' => trans('api-message.USER_NOT_FOUND'),
                ]);
            }

            // If user is deactivated
            if ($user->status == Config::get('constant.INACTIVE_FLAG')) {
                return response()->json([
                    'status' => 0,
                    'message' => trans('api-message.USER_ACCOUNT_INACTIVATED_BY_ADMIN'),
                ]);
            }

            // If user role is super Admin
            if ($user->hasRole(Config::get('constant.SUPER_ADMIN_SLUG'))){
                return response()->json([
                    'status' => 0,
                    'message' => trans('api-message.YOU_CANT_PERFORM_THIS_ACTION'),
                ]);
            }

            // If account already verified
            if ($user->is_verified == 1) {
                return response()->json([
                    'status' => 0,
                    'message' => trans('api-message.USER_ACCOUNT_ALREADY_VERIFIED'),
                ]);
            }

            // Activation otp expired
            $created = new Carbon($user->activation_otp_created_at);
            $now = Carbon::now();
            if ($now->diffInHours($created) > Config::get('constant.ACTIVATION_OTP_EXPIRE_IN_HOURS')){
                return response()->json([
                    'status' => 0,
                    'message' => trans('api-message.OTP_IS_VALID_FOR_48_HOURS'),
                ]);
            }

            // Check Otp
            if ($request->otp == $user->activation_otp) {

                // Set is verified to true 
                $user->update([
                        'is_verified' => Config::get('constant.ACTIVATION_OTP_VERIFIED_TRUE'),
                        'status'=> Config::get('constant.ACTIVE_FLAG'),
                        'activation_otp' => null,
                        'activation_otp_created_at' => null,
                    ]); 
                // Generate auth token
                $token = JWTAuth::fromUser($user);    

                return response()->json([
                    'status' => 1,
                    'message' => trans('api-message.OTP_VERIFIED_SUCCESS'),
                    'data' => [
                        'loginToken' => $token,
                        'user' => new UserResource($user),
                    ],
                ]);
            }else{
                return response()->json([
                    'status' => 0,
                    'message' => trans('api-message.PLEASE_ENTER_VALID_OTP'),
                ]);
            }

            // Return Error Message
            return response()->json([
                'status' => 0,
                'message' => trans('api-message.DEFAULT_ERROR_MESSAGE'),
            ], 500);


        } catch (\Exception $e) {
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

}
