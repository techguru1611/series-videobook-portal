<?php

namespace App\Http\Middleware;

use Closure;
use Config;
use JWTAuth;
use Tymon\JWTAuth\Exceptions\JWTException;

class verifyJWTToken
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle($request, Closure $next)
    {
        try {
            $user = JWTAuth::parseToken()->authenticate();
            if (!$user) {
                return response()->json([
                    'status' => 0,
                    'message' => trans('api-message.FAILED_TO_VALIDATE_TOKEN'),
                ], 401);
            }

            // If user is not verified email
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
                ], 401);
            }
        } catch (JWTException $e) {
            return response()->json([
                'status' => 0,
                'message' => trans('api-message.TOKEN_EXPIRED'),
            ], $e->getStatusCode());
        } catch (\Exception $e) {
            return response()->json([
                'status' => 0,
                'message' => trans('api-message.INVALID_OR_BLACKLISTED_TOKEN'),
            ], $e->getStatusCode());
        }

        return $next($request);
    }
}
