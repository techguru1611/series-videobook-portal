<?php

namespace App\Http\Controllers\Api;

use function foo\func;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Http\Resources\UserResource;
use App\Http\Resources\VideoBookDetailResource;
use App\Http\Resources\VideoSeriesVideoResource;
use App\Models\User;
use App\Models\Setting;
use App\Models\ImageAd;
use App\Models\VideoBook;
use App\Models\VideoBookVideo;
use App\Models\VideoWatchHistory;
use App\Models\VideoAdvertisement;
use Config;
use Log;
use Response;
use Storage;
use Validator;
use JWTAuth;
use DB;

class HomeController extends Controller
{
    /**
     * Get Home data 
     *
     * @return \Illuminate\Http\JsonResponse
    */
    public function getHomeData(Request $request)
    {
        try {
            // Get Login user
            $user = $request->user();
            $userId = $user->id;

            // Get Trending videos series list
             $getSeries = VideoBook::withCount(['users' => function($query){
                            $query->whereBetween('user_purchased_e_video_books.created_at', [date('Y-m-d', strtotime('-15 days')), date('Y-m-d')]);
                        }])->whereHas('users')->orderBy('users_count','DESC')
                 ->whereHas('author', function ($query) {
                     $query->whereNull('deleted_at');
                 })
                 ->limit(Config::get('constant.TRENDING_LIMIT'))
                          ->get();

            // Get series of the Day
            $seriesDetail = Setting::where('slug',Config::get('constant.SERIES_OF_THE_DAY'))->where('status',Config::get('constant.ACTIVE_FLAG'))->first();

            if(!empty($seriesDetail))
            {
            	$seriesId = $seriesDetail->value;
            }

            if (isset($seriesId) && !empty($seriesId)){
                // Get video series details
                $getVideoDetail = VideoBook::with('author')->whereHas('author', function ($query) {
                    $query->whereNull('deleted_at');
                })->find($seriesId);
            }

            // Get series id from setting 
            $setting = Setting::where('slug',Config::get('constant.SOMETHING_NEW_EVERYDAY'))->where('status',Config::get('constant.ACTIVE_FLAG'))->pluck('value')->first();

            if (!empty($setting)){
                $settingSeriesid = explode(",",$setting);
                // Get random videos series list
                $getRandomSeries = VideoBook::withCount('users')->whereHas('author', function ($query) {
                    $query->whereNull('deleted_at');
                })->whereIn('id', $settingSeriesid)->inRandomOrder()->get();
                $randomSeries = VideoBookDetailResource::collection($getRandomSeries);
            }else{
                $randomSeries = [];
            }

            // Get user watched video history
            $getVideoDetails = $user->userVideoHistory()->whereHas('videoBook', function ($query){
                $query->where('status',Config::get('constant.ACTIVE_FLAG'));
            })->where('is_completed',Config::get('constant.IS_COMPLETED_FALSE'))->get();

            // Image Advertisement
            $imageAd = ImageAd::where('status',Config::get('constant.ACTIVE_FLAG'))->orderBy('img_order','ASC')->pluck('image');
            
            $images = [];
            foreach ($imageAd as $image) 
            {
              
                $imageurl = (Storage::disk('public')->exists(Config::get('constant.IMG_AD_ORIGINAL_PHOTO_UPLOAD_PATH') .$image) ? Storage::disk('public')->url(Config::get('constant.IMG_AD_ORIGINAL_PHOTO_UPLOAD_PATH') .$image) : Storage::disk('s3')->url(Config::get('constant.IMG_AD_ORIGINAL_PHOTO_UPLOAD_PATH') . $image));
                
                $images[] = $imageurl;
            }

            // All good so return the response
            return response()->json([
                'status' => 1,
                'message' => trans('api-message.DEFAULT_SUCESS_MESSAGE'),
                'data' =>['seriesOfTheDay' => !empty($getVideoDetail) ? new VideoBookDetailResource($getVideoDetail) : null,
                		  'trendingSeries'=> !empty($getSeries) ? VideoBookDetailResource::collection($getSeries) : null,
                          'getNewEveryday'=> $randomSeries,
                          'videoWatchedByUser'=> VideoSeriesVideoResource::collection($getVideoDetails),
                          'imageAdvertisement' => $images,
                		 ],	
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

    public function getVideoAd(){
     try{

            $getVideoAd = VideoAdvertisement::inRandomOrder()->first();

            if(!$getVideoAd)
            {
                return response()->json([
                    'status' => 0,
                    'message' => trans('api-message.VIDEO_NOT_FOUND')

                ]);
            }

             $videoUrl = (Storage::disk('public')->exists(Config::get('constant.VIDEO_ADVERTISEMENT_ORIGINAL_UPLOAD_PATH') .$getVideoAd->path) ? Storage::disk('public')->url(Config::get('constant.VIDEO_ADVERTISEMENT_ORIGINAL_UPLOAD_PATH') . $getVideoAd->path) : Storage::disk('s3')->url(Config::get('constant.VIDEO_ADVERTISEMENT_ORIGINAL_UPLOAD_PATH') . $getVideoAd->path));


            // All good so return the response
            return response()->json([
                'status' => 1,
                'message' => trans('api-message.DEFAULT_SUCESS_MESSAGE'),
                'data' =>[
                    'videoUrl' => $videoUrl,
                    'isSkipable' => boolval($getVideoAd->is_skipable),
                    'skipaleAfter' => ($getVideoAd->is_skipable == 1) ? $getVideoAd->skipale_after : null,
                    'duration' => ($getVideoAd->length === null || $getVideoAd->length == '') ? 0 : $getVideoAd->length ,


                 ],
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
}

