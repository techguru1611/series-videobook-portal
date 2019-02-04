<?php

namespace App\Http\Controllers\Api;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Http\Resources\AuthorResource;
use App\Http\Resources\VideoBookDetailResource;
use App\Models\User;
use App\Models\VideoBook;
use Config;
use Log;
use Response;
use Validator;
use JWTAuth;
use DB;

class AuthorController extends Controller
{
    /**
     * Get Author Details
     *
     * @param Request $request
     * @return json response from server
     * @throws Exception If there was a any exeption error
     * @Get("author/{id}")
     */
    public function getAuthorDetails(Request $request ,$id)
    {
        try {
            // Get User
            $user = $request->user();
            $userId = $user->id;

            // Get Author
            $author = User::find($id);

            if ($author === null && !isset($author)) {
                return response()->json([
                    'status' => 0,
                    'message' => trans('api-message.AUTHOR_USER_NOT_FOUND'),
                ]);
            }

            $auhtorDetail = User::with(['userSubscriber' =>function($query) use($userId){
                $query->where('users.id',$userId);
            }])->find($id);

            // All good so response success
            return response()->json([
                'status' => 1,
                'message' => trans('api-message.AUTHOR_DETAIL_FETCHED_SUCCESSFULLY'),
                'data' => new AuthorResource($auhtorDetail),
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
     * Get Author series 
     *
     * @param Request $request
     * @return json response from server
     * @throws Exception If there was a any exeption error
     * @Get("author/{id}/series")
     */
    public function getAuthorSeries(Request $request ,$id)
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
            
            // Get User
            $user = $request->user();

            // Get Author
            $author = User::find($id);

            if ($author === null && !isset($author)) {
                return response()->json([
                    'status' => 0,
                    'message' => trans('api-message.AUTHOR_USER_NOT_FOUND'),
                ]);
            }

            $videoSeries = VideoBook::where('user_id',$author->id)
                        ->where('status', Config::get('constant.ACTIVE_FLAG'))
                        ->paginate(Config::get('constant.SERIES_LIST_PER_PAGE'));

            // All good so response success
            return response()->json([
                'status' => 1,
                'message' => trans('api-message.AUTHOR_SERIES_FETCHED_SUCCESSFULLY'),
                'total_count' => $videoSeries->total(),
                'next' => $videoSeries->hasMorePages(),
                'previous' => $videoSeries->onFirstPage() ? false : true,
                'data' => VideoBookDetailResource::collection($videoSeries),
            ]);

        } catch(\Exception $e){
            // Log Message
            Log::error(strtr(trans('log-messages.DEFAULT_ERROR_MESSAGE'), [
                '<Message>' => $e->getMessage(),
            ]));
            return response()->json([
                'status' => 0,
                'error' => $e->getMessage(),
                'message' => trans('api-message.DEFAULT_ERROR_MESSAGE'),
            ], 500);
        }
    }
}
