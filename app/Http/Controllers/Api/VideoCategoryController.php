<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\VideoBookDetailResource;
use App\Http\Resources\VideoCategoryResource;
use App\Models\VideoCategory;
use App\Models\VideoBook;
use Config;
use Illuminate\Http\Request;
use Log;
use Response;
use Validator;
use JWTAuth;

class VideoCategoryController extends Controller
{
    /**
     * Get series of given category
     *
     * @param Request $request
     * @return json response from server
     * @throws Exception If there was a any exeption error
     * @Get("/video-category/{id}/series")
     * @Parameters({
     *      @Parameter("id ", description="the video category id", type="integer" (url)),
     *      @Parameter("page ", description="page number to get data from pagination", type="integer" (Query)),
     * })
     */
    public function getVideoCategorySeries(Request $request, $id)
    {
        try {

            // Rule Validation - Start
            $rule = [
                'page' => 'required|integer|min:1',
            ];

            $validator = Validator::make($request->all(), $rule);

            if ($validator->fails()) {
                return response()->json([
                    'status' => 0,
                    'message' => $validator->messages()->all()[0],
                ]);
            }
            // Rule Validation - Ends

            // Get Video Category
            $videoCategory = VideoCategory::find($id);

            if ($videoCategory === null) {
                return response()->json([
                    'status' => 0,
                    'message' => trans('api-message.VIDEO_CATEGORY_NOT_FOUND'),
                ]);
            }

            // get category_series array value with video minutes
             $series =VideoBook::withCount('users')->whereHas('category' , function($query) use ($id) {
                $query->where('id',$id);
                $query->where('status', Config::get('constant.ACTIVE_FLAG'));
             })->whereHas('author', function ($query) {
                $query->whereNull('deleted_at');
             })->where('status', Config::get('constant.ACTIVE_FLAG'))
                 ->orderBy('created_at', 'desc')
                 ->paginate(Config::get('constant.CATEGORY_LIST_PER_PAGE'));

            return response()->json([
                'status' => 1,
                'message' => trans('api-message.DEFAULT_SUCCESS_MESSAGE'),
                'total_count' => $series->total(),
                'next' => $series->hasMorePages(),
                'previous' => $series->onFirstPage() ? false : true,
                'data' => VideoBookDetailResource::collection($series),
            ]);
        } catch (\Exception $e) {
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
     * Get video category detail
     *
     * @param Request $request
     * @return json response from server
     * @throws Exception If there was a any exeption error
     * @Get("/video-category/{id}")
     * @Parameters({
     *      @Parameter("id ", description="the video category id", type="integer" (url)),
     * })
     * @Transaction({
     *      @Request( {} ),
     *      @Response( {"status": 0,"message": "error message"} )
     *      @Response( {"status": 1,"message": "Success","data": {"id": 1,"name": "Category 1","descr": "Lorem ipsum dolor a sit","image": "","updatedAt": 1540379082,"seriesCount": 6}} )
     * })
     */
    public function getVideoCategoryDetail(Request $request, $id)
    {
        try {
            // Get video Category
            $videoCategory = VideoCategory::find($id);

            if ($videoCategory === null) {
                return response()->json([
                    'status' => 0,
                    'message' => trans('api-message.VIDEO_CATEGORY_NOT_FOUND'),
                ]);
            }

            // Get Categories details
            $videoCategoryDetail = VideoCategory::withCount(['series' => function ($query) {
                $query->whereHas('author', function ($query) {
                    $query->whereNull('deleted_at');
                })->where('status', Config::get('constant.ACTIVE_FLAG'))
                    ->where('approved_by', Config::get('constant.APPROVED_VIDEO_FLAG'));
            }])->where('id', $id)->first();

            return response()->json([
                'status' => 1,
                'message' => trans('api-message.DEFAULT_SUCCESS_MESSAGE'),
                'data' => new VideoCategoryResource($videoCategoryDetail),
            ]);
        } catch (\Exception $e) {
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
     * Get video category List
     *
     * @param Request $request
     * @return json response from server
     * @throws Exception If there was a any exeption error
     * @Get("video-category/list")
     * @Parameters({
     *      @Parameter("page", description="Pagination", type="integer (Query)"),
     * })
     * @Transaction({
     *      @Request( {} ),
     *      @Response( {"status": 0,"message": "error message"} )
     *      @Response( {"status": 1,"message": "Success","data": {"id": 1,"name": "Category 1","descr": "Lorem ipsum dolor a sit","image": "","updatedAt": 1540379082,"seriesCount": 6}} )
     * })
     */
    public function list(Request $request){
        try{
            // Rule Validation - Start
            $rule = [
                'page' => 'required|integer|min:1',
            ];

            $validator = Validator::make($request->all(), $rule);

            if ($validator->fails()) {
                return response()->json([
                    'status' => 0,
                    'message' => $validator->messages()->all()[0],
                ]);
            }
            // Rule Validation - Ends

            // Get active category videos list
            $userId = $request->user()->id;
            $getCategory = VideoCategory::withCount(['series' => function ($query) {
                $query->whereHas('author', function ($query) {
                    $query->whereNull('deleted_at');
                })->where('status', Config::get('constant.ACTIVE_FLAG'))
                    ->where('approved_by', Config::get('constant.APPROVED_VIDEO_FLAG'));
            }])->with(['users' => function ($query) use ($userId) {
                $query->where('users.id', $userId);
            }])
            ->paginate(Config::get('constant.CATEGORY_LIST_PER_PAGE'));
            
            // Get response success
            return response()->json([
                'status' => 1,
                'message' => trans('api-message.DEFAULT_SUCCESS_MESSAGE'),
                'total_count' => $getCategory->total(),
                'next' => $getCategory->hasMorePages(),
                'previous' => $getCategory->onFirstPage() ? false : true,
                'data' => VideoCategoryResource::collection($getCategory),
            ]);

        } catch(\Exception $e){
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
     * Get  Now Trending Category List
     *
     * @param Request $request
     * @return json response from server
     * @throws Exception If there was a any exeption error
     * @Get("video-category/trending?page=1")
     */

    public function nowTrendingCategoryList(Request $request){
        try{
            // Rule Validation - Start
            $rule = [
                'page' => 'required|integer|min:1',
            ];

            $validator = Validator::make($request->all(), $rule);

            if ($validator->fails()) {
                return response()->json([
                    'status' => 0,
                    'message' => $validator->messages()->all()[0],
                ]);
            }
            // Rule Validation - Ends

            // Get User
            $user = $request->user();

            // Get active category videos list
             $getSeries = VideoCategory::withCount(['series' => function ($query) {
                 $query->whereHas('author', function ($query) {
                     $query->whereNull('deleted_at');
                 })->where('status', Config::get('constant.ACTIVE_FLAG'))
                     ->where('approved_by', Config::get('constant.APPROVED_VIDEO_FLAG'));
             }])->with('users')->withCount('users')->orderBy('users_count', 'DESC')->whereBetween('created_at', [date('Y-m-d H:i:s', strtotime('-50 days')), date('Y-m-d H:i:s')])->paginate(Config::get('constant.CATEGORY_LIST_PER_PAGE'));
        
            // All good so retuen response success
            return response()->json([
                'status' => 1,
                'message' => trans('api-message.NOW_TREADING_CATEGORY_LIST_FETCHED_SUCCESSFULLY'),
                'total_count' => $getSeries->total(),
                'next' => $getSeries->hasMorePages(),
                'previous' => $getSeries->onFirstPage() ? false : true,
                'data' => VideoCategoryResource::collection($getSeries),
            ]);

        } catch(\Exception $e){
            // Log Message
            Log::error(strtr(trans('log-messages.DEFAULT_ERROR_MESSAGE'), [
                '<Message>' => $e->getMessage(),
            ]));
            return response()->json([
                'status' => 0,
                'message' => trans('api-message.ERROR_RETRIVING_SERIES_LIST'),
            ], 500);
        }
    }

    public function categoryLists(){
        try{
            $getCategory = VideoCategory::withCount(['series' => function ($query) {
                $query->whereHas('author', function ($query) {
                    $query->whereNull('deleted_at');
                })->where('status', Config::get('constant.ACTIVE_FLAG'));
            }])->where('status', Config::get('constant.ACTIVE_FLAG'))->get();

            return response()->json([
                'status' => 1,
                'message' => trans('api-message.DEFAULT_SUCCESS_MESSAGE'),
                'data' => VideoCategoryResource::collection($getCategory),
            ]);
        } catch(\Exception $e){
            // Log Message
            Log::error(strtr(trans('log-messages.DEFAULT_ERROR_MESSAGE'), [
                '<Message>' => $e->getMessage(),
            ]));
            return response()->json([
                'status' => 0,
                'message' => trans('api-message.ERROR_RETRIVING_SERIES_LIST'),
            ], 500);
        }
    }
}
