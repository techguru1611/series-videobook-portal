<?php

namespace App\Http\Controllers\Api;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Models\Cms;
use Config;
use DB;
use Log;

class AboutUsController extends Controller
{
    /**
     * Get About detail
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function aboutUs(Request $request)
    {
        // Start DB Transaction
        DB::beginTransaction();
        try {
           
           	$cms = Cms::where('slug',Config::get('constant.ABOUT_US'))->first();

            // All good so return the response
            return response()->json([
                'status' => 1,
                'message' =>trans('api-message.DEFAULT_SUCESS_MESSAGE'),
                'data' => ['content' =>$cms->value],
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
}
