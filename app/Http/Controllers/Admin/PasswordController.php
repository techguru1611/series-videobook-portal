<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Mail\PasswordChanged;
use App\Models\PasswordReset;
use App\Models\User;
use Carbon\Carbon;
use Config;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Redirect;
use Validator;

class PasswordController extends Controller
{

    private $passwordChangedMail;

    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct(PasswordChanged $passwordChangedMail)
    {
        $this->passwordChangedMail = $passwordChangedMail;
    }

    /**
     * Check a reset token and update password for password recovery.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function set(Request $request)
    {
        // Rule Validation - Start
        $rule = [
            'email' => 'required|email',
            'password' => 'required|min:6|max:20|confirmed',
        ];

        $validator = Validator::make($request->all(), $rule);

        if ($validator->fails()) {
            return Redirect::back()->withInput($request->all())->withErrors($validator);
        }
        // Rule validation - Ends

        // Find password reset token
        $this->findPasswordResetToken($request);

        // Get User data
        $user = $this->getUserToResetPassword($request);
        // Update Password
        $this->updatePassword($user, $request);
        // Send change password mail
        $this->sendPassChangedMail($user);

        // All good so return with message
        return Redirect::back()->with('success', [
            trans('admin-message.PASSWORD_RESET_SUCCESS_MESSAGE'),
        ]);
    }

    /**
     * Get user to reset password
     *
     * @param \Illuminate\Http\Request  $request
     * @return mixed User object if success, or return view with message if fail
     */
    private function getUserToResetPassword($request)
    {
        $user = User::where('email', $request->email)->first();

        // If user not found
        if (is_null($user)) {
            return Redirect::back()->withInput($request->all())->withErrors([
                trans('admin-message.USER_NOT_EXIST'),
            ]);
        }

        // If user is deactivated
        if ($user->status != Config::get('constant.ACTIVE_FLAG')) {
            return Redirect::back()->withInput($request->all())->withErrors([
                trans('admin-message.USER_DEACTIVATED_ERROR_MESSAGE'),
            ]);
        }

        // If user is not verified
        if ($user->is_verified != 1) {
            return Redirect::back()->withInput($request->all())->withErrors([
                trans('admin-message.USER_NOT_VERIFIED_ERROR_MESSAGE'),
            ]);
        }
        return $user;
    }

    /**
     * Find user reset token to reset password
     *
     * @param \Illuminate\Http\Request  $request
     * @return mixed boolean if success, or return view with message if fail
     */
    private function findPasswordResetToken($request)
    {
        $passwordReset = PasswordReset::where('email', $request->email)->first();

        // Password reset request not found
        if (is_null($passwordReset)) {
            return Redirect::back()->withInput($request->all())->withErrors([
                trans('admin-message.INVALID_PASSWORD_RESET_TOKEN'),
            ]);
        }

        // Password reset token expired
        if (Carbon::now()->diffInMinutes($passwordReset->created_at) > PasswordReset::RESET_PASSWORD_EXPIRE_IN_MINUTES) {
            return Redirect::back()->withInput($request->all())->withErrors([
                trans('admin-message.RESET_PASSWORD_LINK_EXPIRED_MESSAGE'),
            ]);
        }

        // Check token
        if (!Hash::check($request->token, $passwordReset->token)) {
            return Redirect::back()->withInput($request->all())->withErrors([
                trans('admin-message.INVALID_PASSWORD_RESET_TOKEN'),
            ]);
        }

        return true;
    }

    /**
     * Update new password.
     *
     * @param  \App\User  $user
     * @param  \Illuminate\Http\Request  $request
     * @return void
     */
    private function updatePassword($user, $request)
    {
        User::where('id', $user->id)->update(['password' => bcrypt($request->password)]);
        PasswordReset::where('email', $user->email)->delete();
    }

    /**
     * Send email to user.
     *
     * @param  \App\User  $user
     * @return void
     */
    private function sendPassChangedMail($user)
    {
        $this->passwordChangedMail->setUser($user);
        Mail::to($user->email)->send($this->passwordChangedMail);
    }

}
