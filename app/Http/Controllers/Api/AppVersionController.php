<?php

namespace App\Http\Controllers\Api;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Validation\Rule;
use  App\Models\AppVersion;
use Validator;
use Config;
use Log;

class AppVersionController extends Controller
{
    /**
     * Get App version detail
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
    */
    public function getAppVersionDetail(Request $request)
    {
        try {
            // Rule Validation - Start
            $rule = [
            	'platform' => ['required', Rule::in([Config::get('constant.ANDROID'), Config::get('constant.IOS')])],
                'appVersion' =>'required|integer'
                
            ];

            $validator = Validator::make($request->all(), $rule);

            if ($validator->fails()) {
                return response()->json([
                    'status' => 0,
                    'message' => $validator->messages()->all()[0],
                ]);
            }
            // Rule Validation - Ends
           
            // Get Latest Version
           	$appData = AppVersion::where('device_type',$request->platform)->orderBy('id','DESC')->first();
           	$latestVersion = $appData->app_version;    

           	if($latestVersion > $request->appVersion)
           	{
           		$updateAvailable = Config::get('constant.UPDATE_AVAILABLE_TRUE');
           	}
           	else
           	{
           		$updateAvailable = Config::get('constant.UPDATE_AVAILABLE_FALSE');
           	}

           	// Check for Force update 	
           	if($forceUpdateData = AppVersion::where('app_version','>',$request->appVersion)->where('device_type',$request->platform)->where('force_update',Config::get('constant.FORCE_UPDATE_TRUE'))->exists())
           	{
           		$forceUpdate = Config::get('constant.FORCE_UPDATE_TRUE');
           	}
           	else
           	{
           		$forceUpdate = Config::get('constant.FORCE_UPDATE_FALSE');
           	}

            // All good so return the response
            return response()->json([
                'status' => 1,
                'message' =>trans('api-message.DEFAULT_SUCESS_MESSAGE'),
                'data' => ['latestVersion' => $latestVersion,
                			'updateAvailable' => $updateAvailable,
                			'forceUpdate' => $forceUpdate, 
            			  ]
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
}
