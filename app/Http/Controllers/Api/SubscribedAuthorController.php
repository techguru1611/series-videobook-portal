<?php

namespace App\Http\Controllers\Api;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Http\Resources\AuthorResource;
use App\Http\Resources\VideoBookDetailResource;
use App\Models\User;
use App\Models\VideoBook;
use App\Models\UserFavorite;
use Config;
use Log;
use Response;
use Validator;
use JWTAuth;
use DB;

class SubscribedAuthorController extends Controller
{
    /**
     * Get subscribed Author List
     *
     * @param Request $request
     * @return json response from server
     * @throws Exception If there was a any exeption error
     * @Get("user/me/author/subscribed?page=1)
     */
    public function subscribedAuthorList(Request $request)
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

                $getUserAuthors = User::with('userSubscribe')->find($user->id);

                // Get subscribed author list              
                $subscribedAuthor = $getUserAuthors->userSubscribe()->paginate(Config::get('constant.AUTHOR_PER_PAGE'));

                // All good so response success
                return response()->json([
                    'status' => 1,
                    'message' => trans('api-message.SUBSCRIBED_AUTHOR_FETCHED_SUCCESSFULLY'),
                    'total_count' => $subscribedAuthor->total(),
                    'next' => $subscribedAuthor->hasMorePages(),
                    'previous' => $subscribedAuthor->onFirstPage() ? false : true,
                    'data' => AuthorResource::collection($subscribedAuthor),
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
     * Get subscribed Author video Series List
     *
     * @param Request $request
     * @return json response from server
     * @throws Exception If there was a any exeption error
     * @Get("user/me/author/subscribed/series?page=1)
     */
    public function subscribedAuthorVideoList(Request $request)
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

                // Get subscribed author video list within 15 dyas
                $authorsIds = User::join('user_favorite_list', 'user_favorite_list.author_id', '=', 'users.id')
                			->selectRaw('user_favorite_list.author_id')
                			->where('user_favorite_list.user_id', $user->id)
                			->where('user_favorite_list.author_id', '<>', $user->id)
                			->groupBy('author_id')
                			->pluck('author_id')
                			->toArray();

                $videoBooks = VideoBook::whereIn('user_id',$authorsIds)->where('status', Config::get('constant.ACTIVE_FLAG'))->paginate(Config::get('constant.SERIES_LIST_PER_PAGE')); 			 
     
                // All good so response success
                return response()->json([
                    'status' => 1,
                    'message' => trans('api-message.SUBSCRIBED_AUTHOR_VIDEO_FETCHED_SUCCESSFULLY'),
                    'total_count' => $videoBooks->total(),
                    'next' => $videoBooks->hasMorePages(),
                    'previous' => $videoBooks->onFirstPage() ? false : true,
                    'data' => VideoBookDetailResource::collection($videoBooks),
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
     * Subscribed Author 
     *
     * @param Request $request
     * @return json response from server
     * @throws Exception If there was a any exeption error
     * @post("user/me/author/save)
     */
    public function subscribedAuthor(Request $request)
    {

        // Start DB transaction
        DB::beginTransaction();
        try {
                // Rule Validation - Start
                $rule = [
                    'id' => 'required|integer',
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
                $userId = $user->id;

                // Get Author
                $author = User::find($request->id);

                if ($author === null && !isset($author)) {
                    return response()->json([
                        'status' => 0,
                        'message' => trans('api-message.AUTHOR_USER_NOT_FOUND'),
                    ]);
                }

                $authorExists = UserFavorite::where('user_id',$userId)->where('author_id',$request->id)->first();
                
                if(!empty($authorExists))
                {
                    // Return Messege already subscribed author
                    return response()->json([
                    'status' => 0,
                    'message' => trans('api-message.AUTHOR_ALREADY_SUBSCRIBED'),
                     ]);       
                }
                else{

                    $userFavrorite = new UserFavorite();
                    $userFavrorite->user_id = $userId;
                    $userFavrorite->author_id = $request->id;
                    $userFavrorite->save();     
                }
                
                // Commit to DB
                DB::commit();

                $auhtorDetail = User::with(['userSubscriber' =>function($query) use($userId){
                    $query->where('users.id',$userId);
                }])->find($request->id);
        
                // All good so response success
                return response()->json([
                    'status' => 1,
                    'message' => trans('api-message.SUBSCRIBED_AUTHOR_ADDED_SUCCESSFULLY'),
                    'data' => new AuthorResource($auhtorDetail)
                ]);

        } catch(\Exception $e){
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
     * Unsubscribed Author 
     *
     * @param Request $request
     * @return json response from server
     * @throws Exception If there was a any exeption error
     * @post("user/me/author/unsubscribed)
     */
    public function unsubscribedAuthor(Request $request)
    {
        // Start DB transaction
        DB::beginTransaction();
        try {
                // Rule Validation - Start
                $rule = [
                    'id' => 'required|integer',
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
                $userId = $user->id;

                // Get Author
                $author = User::find($request->id);

                if ($author === null && !isset($author)) {
                    return response()->json([
                        'status' => 0,
                        'message' => trans('api-message.AUTHOR_USER_NOT_FOUND'),
                    ]);
                }

                // Check for already exists auhtor
                $authorExists = UserFavorite::where('user_id',$userId)->where('author_id',$request->id)->first();

                

                

                if(empty($authorExists))
                {
                     return response()->json([
                    'status' => 0,
                    'message' => trans('api-message.AUTHOR_USER_NOT_FOUND'),
                     ]);       
                }
                else{

                    $deleteRecord = UserFavorite::find($authorExists->id);
                    $deleteRecord->delete();


                    // Commit to DB
                    DB::commit();

                    $auhtorDetail = User::with(['userSubscriber' =>function($query) use($userId){
                            $query->where('users.id',$userId);
                    }])->find($request->id);
                    

                    // All good so response success
                    return response()->json([
                        'status' => 1,
                        'message' => trans('api-message.UNSUBSCRIBED_AUTHOR_SUCCESSFULLY'),
                        'data' => new AuthorResource($auhtorDetail)
                    ]);
                }

        } catch(\Exception $e){
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


}
