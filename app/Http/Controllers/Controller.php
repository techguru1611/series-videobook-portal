<?php

namespace App\Http\Controllers;

use Facebook\Exceptions\FacebookResponseException as FacebookResponseException;
use Facebook\Exceptions\FacebookSDKException as FacebookSDKException;
use Google_Client;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Foundation\Bus\DispatchesJobs;
use Illuminate\Foundation\Validation\ValidatesRequests;
use Illuminate\Routing\Controller as BaseController;
use Illuminate\Support\Facades\Config;
use Log;
use Socialite;

class Controller extends BaseController
{
    use AuthorizesRequests, DispatchesJobs, ValidatesRequests;

    protected function validateFacebookLogin($accessToken)
    {
        try {
            $response = Socialite::driver('facebook')->userFromToken($accessToken);

            // Return user profile info
            return [
                'id' => $response['id'],
                'email' => isset($response['email']) ? $response['email'] : '',
                'full_name' => $response['name'],
            ];
        } catch (FacebookResponseException $e) {
            // Log social login error messages
            Log::error(strtr(trans('log-messages.SOCIAL_AUTHENTICATION_ERROR'), [
                '<Message>' => $e->getMessage(),
            ]));

            return [
                'status' => 0,
                'message' => trans('api-message.SOCIAL_AUTHENTICATION_ERROR_MESSAGE'),
                'code' => 401,
            ];
        } catch (FacebookSDKException $e) {
            // Log social login error messages
            Log::error(strtr(trans('log-messages.SOCIAL_AUTHENTICATION_ERROR'), [
                '<Message>' => $e->getMessage(),
            ]));

            return [
                'status' => 0,
                'message' => trans('api-message.SOCIAL_AUTHENTICATION_ERROR_MESSAGE'),
                'code' => 401,
            ];
        } catch (\Exception $e) {
            // Log social login error messages
            Log::error(strtr(trans('log-messages.SOCIAL_AUTHENTICATION_ERROR'), [
                '<Message>' => $e->getMessage(),
            ]));

            return [
                'status' => 0,
                'message' => trans('api-message.SOCIAL_AUTHENTICATION_ERROR_MESSAGE'),
                'code' => 500,
            ];
        }
    }

    protected function validateGoogleLogin($idToken, $platform)
    {
        try {
            if ($platform == Config::get('constant.IOS')){
                $clientId = Config::get('services.ios_google_id');
            } elseif ($platform == Config::get('constant.ANDROID')){
                $clientId = Config::get('services.android_google_id');
            } else {
                $clientId = Config::get('services.google_id');
            }

            $client = new Google_Client(['client_id' => $clientId]); // Specify the CLIENT_ID of the app that accesses the backend
            $payload = $client->verifyIdToken($idToken);
            if (!$payload) {
                // Log social login error messages
                Log::error(strtr(trans('log-messages.SOCIAL_AUTHENTICATION_ERROR'), [
                    '<Message>' => "Invalid google id_token found: Token: {$idToken}",
                ]));

                // Unauthorize action found
                return [
                    'status' => 0,
                    'message' => trans('api-message.INVALID_AUTH_TOKEN'),
                    'code' => 401,
                ];
            }

            // Return user profile info
            return [
                'id' => $payload['sub'],
                'email' => $payload['email'],
                'full_name' => $payload['name'],
            ];
        } catch (\Exception $e) {
            // Log social login error messages
            Log::error(strtr(trans('log-messages.SOCIAL_AUTHENTICATION_ERROR'), [
                '<Message>' => $e->getMessage(),
            ]));

            return [
                'status' => 0,
                'message' => trans('api-message.SOCIAL_AUTHENTICATION_ERROR_MESSAGE'),
                'code' => 200,
            ];
        }
    }

}
