<?php

namespace App\Http\Controllers\Api;

use App\Helpers\Helpers;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use App\Http\Resources\VideoBookVideoResource;
use App\Http\Resources\VideoSeriesVideoResource;
use App\Http\Resources\VideoAdResource;
use App\Http\Resources\CommentResource;
use App\Models\VideoBookVideo;
use App\Models\VideoBook;
use App\Models\VideoLike;
use App\Models\VideoComment;
use App\Models\VideoWatchHistory;
use App\Models\VideoAdvertisement;
use App\Models\User;
use Config;
use Log;
use Response;
use Validator;
use JWTAuth;
use DB;
use Carbon\Carbon;


class VideoBookVideoController extends Controller
{

    public function __construct()
    {
        $this->videoTempPath = Config::get('constant.SERIES_VIDEO_TEMP_UPLOAD_PATH');
        $this->videoPath = Config::get('constant.SERIES_VIDEO_UPLOAD_PATH');
    }
    /**
     * Save video like/unlike 
     *
     * @param Request $request
     * @return json response from server
     * @throws Exception If there was a any exeption error
     * @Get("video-like-save")
     */

    public function videoLikeSave(Request $request)
    {
        // Start Trasaction DB
        DB::beginTransaction();
        try{
        	// Rule Validation - Start
            $rule = [
                'id' => 'required|max:100',
                'status' => ['required', Rule::in([Config::get('constant.LIKE'), Config::get('constant.UNLIKE'),Config::get('constant.REVERT')])],
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

       		// Get video 
            $video = VideoBookVideo::find($request->id);
           
            if ($video === null && !isset($video)) {
                return response()->json([
                    'status' => 0,
                    'message' => trans('api-message.VIDEO_NOT_FOUND'),
                ]);
            }

            $videoBookId = $video->e_video_book_id;
            $videoId = $video->id;

            // Get video series which purchased by user
            $getSeries = VideoBook::find($videoBookId);

            if ($getSeries->user_id !== $userId){
                // Get video series which purchased by user
                $getSeries = VideoBook::with('author')->withCount(['users' => function ($query) use ($user) {
                    $query->where('users.id', $user->id);
                }])->find($videoBookId);


                if(empty($getSeries) || $getSeries->users_count == 0)
                {
                    return response()->json([
                        'status' => 0,
                        'message' => trans('api-message.USER_NOT_PURCHASED_VIDEOSERIES'),
                    ]);
                }
            }

	    // Like
        if($request->status === Config::get('constant.LIKE'))
            {
	            $getVideoLike = VideoLike::where('user_id',$user->id)->where('video_id',$videoId)->first();

	            if(!empty($getVideoLike))
	            {
	            	if($getVideoLike->action === Config::get('constant.LIKE'))
	            	{
		            	return response()->json([
		                    'status' => 0,
		                    'message' => trans('api-message.YOU_HAVE_ALREADY_LIKED_THIS_VIDEO'),
		                ]);
	                }else{	                	
		            		VideoLike::where('user_id',$user->id)->update(['action' =>Config::get('constant.LIKE')]);
		            		 //commit DB
						     DB::commit();

		            		// Upadte success message
		            		return response()->json([
				                    'status' => 1,
				                    'message' => trans('api-message.LIKE_SAVED_SUCCESSFULLY'),
		                    ]);
		            	}
				    }else{

                	    // New record insert
                		$videoLike = new VideoLike();
						$videoLike['user_id'] = $user->id;
						$videoLike['video_id'] = $videoId;
						$videoLike['action'] = Config::get('constant.LIKE');
						$videoLike->save();

						//commit DB
						DB::commit();

						// Save like success message
	                    return response()->json([
				            'status' => 1,
				             'message' => trans('api-message.LIKE_SAVED_SUCCESSFULLY'),
		                     ]);
				    }
            }

        // Unlike 	
        if($request->status === Config::get('constant.UNLIKE'))
           {
	            $getVideoLike = VideoLike::where('user_id',$user->id)->where('video_id',$videoId)->first();

	            if(!empty($getVideoLike))
	            {
	            	
	            	if($getVideoLike->action === Config::get('constant.UNLIKE')) {
		            	return response()->json([
		                    'status' => 0,
		                    'message' => trans('api-message.YOU_HAVE_ALREADY_UNLIKED_THIS_VIDEO'),
		                ]);
	                }else{
		            		VideoLike::where('user_id',$user->id)->update(['action' => Config::get('constant.UNLIKE')]);

		            		//commit DB
						    DB::commit();

		            		// Upadte success message
		            		return response()->json([
				                    'status' => 1,
				                    'message' => trans('api-message.UNLIKE_SAVED_SUCCESSFULLY'),
		                     ]);	
				        }
                }
                else{
						// New record insert
            			$videoLike = new VideoLike(); 
						$videoLike['user_id'] = $user->id;
						$videoLike['video_id'] = $videoId;
						$videoLike['action'] = Config::get('constant.UNLIKE');
						$videoLike->save();

						//commit DB
						DB::commit();

						// Save like success message
	                    return response()->json([
				            'status' => 1,
				            'message' => trans('api-message.UNLIKE_SAVED_SUCCESSFULLY'),
		                  ]);
				    }
		        }
         
        // Revert
        if($request->status === Config::get('constant.REVERT'))
            {
            	$getVideoLike = VideoBookVideo::withCount(['videoLikesByUser'=>function($query) use ($user) {
            		    $query->where('users.id', $user->id);
            	   }])->withCount(['videoUnlikesByUser'=>function($query) use ($user) {
                        $query->where('users.id', $user->id);
                   }])->find($request->id);
            	
            	$uesrLikeCount = $getVideoLike->video_likes_by_user_count;

                $userUnlikeCount = $getVideoLike->video_unlikes_by_user_count;

            	if($uesrLikeCount != 0 || $userUnlikeCount != 0)
	            {
	            	$getVideoLike->videoLikesByUser()->detach($user->id);

                    $getVideoLike->videoUnlikesByUser()->detach($user->id);
						 
					DB::commit();

					// Revert success message
	                return response()->json([
				            'status' => 1,
				            'message' => trans('api-message.REVERT_SUCCESSFULLY'),
		            ]);
				}else{
					
					// Return Error Message
					return response()->json([
				            'status' => 0,
				            'message' => trans('api-message.NOTHING_FOR_REVERT'),
		                   ]);
				        }
		    }	
        } catch(\Exception $e){
            DB::rollback();
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
     * Get video details 
     *
     * @param Request $request
     * @return json response from server
     * @throws Exception If there was a any exeption error
     * @Get("getvideodetails/{id}")
     */

    public function getVideoDetails(Request $request , $id)
    {

        try{     
       		// Get User
            $user = $request->user();

       		// Get video 
            $video = VideoBookVideo::find($id);
          
            if(empty($video)) {
                return response()->json([
                    'status' => 0,
                    'message' => trans('api-message.VIDEO_NOT_FOUND'),
                ]);
            }

            $videoBookId = $video->e_video_book_id;
            $videoId = $video->id;

            $series = VideoBook::withTrashed()->find($videoBookId);

            if ($series->user_id == $user->id){

                $getVideoDetail = VideoBookVideo::with(['likes' => function ($query) use ($user){
                    $query->where('user_id', $user->id);
                }])->with('videobook','videobook.author')->with(['videoWatchedByUser' => function ($query) use ($user){
                    $query->where('users.id', $user->id);
                }])->find($videoId);

            } else{
            
                // Get video series which purchased by user
                $getSeries = VideoBook::with('author')->withCount(['users' => function ($query) use ($user) {
                    $query->where('users.id', $user->id);
                }])->find($videoBookId);
                
                if(empty($getSeries) || $getSeries->users_count == 0)
                {
                    return response()->json([
                        'status' => 0,
                        'message' => trans('api-message.USER_NOT_PURCHASED_VIDEOSERIES'),
                    ]);
                }

                $getVideoDetail = VideoBookVideo::with(['likes' => function ($query) use ($user){
                    $query->where('user_id', $user->id);
                }])->with('videobook','videobook.author')->with(['videoWatchedByUser' => function ($query) use ($user){
                    $query->where('users.id', $user->id);
                }])->where('is_approved',Config::get('constant.APPROVED_VIDEO_FLAG'))->find($videoId);

                if (!$getVideoDetail){
                    return response()->json([
                        'status' => 0,
                        'message' => trans('api-message.VIDEO_NOT_APPROVED'),
                    ]);
                }
            }


            if ($getVideoDetail->is_transacoded == Config::get('constant.TRANSCODING_DONE_VIDEO_STATUS')){
                $object_key = $this->videoPath.$getVideoDetail->path;
            } elseif ($getVideoDetail->is_transacoded == Config::get('constant.TRANSCODING_FAILED_VIDEO_STATUS')) {
                $object_key = $this->videoTempPath.$getVideoDetail->path;
            } else {
                $object_key = null;
            }
            isset($object_key) && !empty($object_key) ? $getVideoDetail->videoSignedUrl = Helpers::generateAWSSignedUrl($object_key) : $getVideoDetail->videoSignedUrl = null ;

            $getVideoAd = VideoAdvertisement::where('status',Config::get('constant.ACTIVE_FLAG'))->inRandomOrder()->limit(1)->get();

            // All good so retuen response success
            return response()->json([
                'status' => 1,
                'message' => trans('api-message.VIDEO_DETAILS_FETCHED_SUCCESSFULLY'),
                'data' => [
                    'videoDetails' => new VideoBookVideoResource($getVideoDetail),
                    'videoAd' => VideoAdResource::collection($getVideoAd),
                ],

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
     * Save video comments 
     *
     * @param Request $request
     * @return json response from server
     * @throws Exception If there was a any exeption error
     * @post('video/comment')
    */

    public function saveVideoComment(Request $request)
    {
        // Start DB transaction
        DB::beginTransaction();
        try{
            // Rule Validation - Start
            $rule = [
                'video_id' => 'required|max:100',
                'comment' => 'required',
                'comment_id' => 'required|integer',
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
            $userId = $request->user()->id; 

            // Get video 
            $video = VideoBookVideo::find($request->video_id);
           
            if(empty($video)) {
                return response()->json([
                    'status' => 0,
                    'message' => trans('api-message.VIDEO_NOT_FOUND'),
                ]);
            }

            $videoBookId = $video->e_video_book_id;
            $videoId = $video->id;

            // Get video series which purchased by user
            $getSeries = VideoBook::find($videoBookId);

            if ($getSeries->user_id !== $userId){
                // Get video series which purchased by user
                $getSeries = VideoBook::with('author')->withCount(['users' => function ($query) use ($user) {
                    $query->where('users.id', $user->id);
                }])->find($videoBookId);


                if(empty($getSeries) || $getSeries->users_count == 0)
                {
                    return response()->json([
                        'status' => 0,
                        'message' => trans('api-message.USER_NOT_PURCHASED_VIDEOSERIES'),
                    ]);
                }
            }

            $commentId = $request->comment_id ;

            if($commentId == 0)
            {
                $videoComment = new VideoComment(); 
                $videoComment['e_video_book_id'] = $videoBookId;
                $videoComment['e_video_book_video_id'] = $videoId;
                $videoComment['parent_id'] = $request->comment_id;
                $videoComment['comment'] = $request->comment;
                $videoComment['type'] = Config::get('constant.PARENT');
                $videoComment['replied_to'] = $commentId;
                $videoComment['comment_by'] = $userId;
                $videoComment['status'] = Config::get('constant.ACTIVE_FLAG');
                $videoComment->save();

                // Commit to DB
                DB::commit();

                $getVideoCommentsDetail = $video->comments()->with('videoCommentedUser')->where('type',Config::get('constant.PARENT'))->where('parent_id',$request->comment_id)->orderBy('created_at','DESC')->first();

                // Save comment success message
                return response()->json([
                    'status' => 1,
                    'message' => trans('api-message.COMMENT_SAVED_SUCCESSFULLY'),
                    'data' => new CommentResource($getVideoCommentsDetail),

                ]);
            }
            else
            {
                $videoComment = new VideoComment(); 
                $videoComment['e_video_book_id'] = $videoBookId;
                $videoComment['e_video_book_video_id'] = $videoId;
                $videoComment['parent_id'] = $request->comment_id;
                $videoComment['comment'] = $request->comment;
                $videoComment['type'] = Config::get('constant.REPLIED');
                $videoComment['replied_to'] = $userId;
                $videoComment['comment_by'] = $userId;
                $videoComment['status'] = Config::get('constant.ACTIVE_FLAG');
                $videoComment->save();

                // Commit to DB
                DB::commit();

                $getVideoCommentsDetail = $video->comments()->with('videoCommentedUser')->where('type',Config::get('constant.REPLIED'))->where('parent_id',$request->comment_id)->orderBy('created_at','DESC')->first();

                // Save comment success message
                return response()->json([
                    'status' => 1,
                    'message' => trans('api-message.COMMENT_SAVED_SUCCESSFULLY'),
                    'data' => new CommentResource($getVideoCommentsDetail),
                ]);
            }
        } catch(\Exception $e){
            DB::rollback();
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
     * Save video history 
     *
     * @param Request $request
     * @return json response from server
     * @throws Exception If there was a any exeption error
     * @post('video/history')
    */

    public function saveVideoHistory(Request $request)
    {
        // Start DB transaction
        DB::beginTransaction();
        try{
            // Rule Validation - Start
            $rule = [
                'video_id' => 'required|max:100',
                'watched_till' => 'nullable|integer',
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
            $userId = $request->user()->id; 

            // Get video 
            $video = VideoBookVideo::find($request->video_id);

            
            if(empty($video)) {
                return response()->json([
                    'status' => 0,
                    'message' => trans('api-message.VIDEO_NOT_FOUND'),
                ]);
            }

            $videoBookId = $video->e_video_book_id;
            $videoId = $video->id;
            $totalDuration = $video->lengh;

            // Get video series which purchased by user
            $getSeries = VideoBook::find($videoBookId);

            if ($getSeries->user_id !== $userId){
                // Get video series which purchased by user
                $getSeries = VideoBook::with('author')->withCount(['users' => function ($query) use ($user) {
                    $query->where('users.id', $user->id);
                }])->find($videoBookId);


                if(empty($getSeries) || $getSeries->users_count == 0)
                {
                    return response()->json([
                        'status' => 0,
                        'message' => trans('api-message.USER_NOT_PURCHASED_VIDEOSERIES'),
                    ]);
                }
            }

            if($request->watched_till > $totalDuration)
            {
                    // Error message
                    return response()->json([
                        'status' => 0,
                        'message' => trans('api-message.WATCHED_TILL_IS_GREATER_THAN_TOTAL_DURATION'),
                    ]);
            }

            $historyExists = VideoWatchHistory::where('user_id',$userId)->where('e_video_book_videos_id',$videoId)->first();

            if($historyExists != null &&  $historyExists->is_completed == 1 )
            {
                    // Save success message
                    return response()->json([
                        'status' => 1,
                        'message' => trans('api-message.VIDEO_HISTORY_SAVED_SUCCESSFULLY'),
                    ]);
            }

            if($totalDuration == $request->watched_till)
            {
                $isCompleted = Config::get('constant.IS_COMPLETED_TRUE');
            }else{
                $isCompleted = Config::get('constant.IS_COMPLETED_FALSE');
            }
            if($historyExists != null)
            {
                    $historyExists->update(['is_completed' => $isCompleted ,'watched_till'=> $request->watched_till ,'last_watched_at' => Carbon::now()]);

                    // Commit to DB
                    DB::commit();

                    // Save like success message
                    return response()->json([
                        'status' => 1,
                        'message' => trans('api-message.VIDEO_HISTORY_UPDATED_SUCCESSFULLY'),
                    ]);
            }
            else{
                    $videoHistory= new VideoWatchHistory(); 
                    $videoHistory['user_id'] = $userId;
                    $videoHistory['e_video_book_videos_id'] = $videoId;
                    $videoHistory['is_completed'] = $isCompleted;
                    $videoHistory['watched_till'] = $request->watched_till;
                    $videoHistory['last_watched_at'] = Carbon::now();
                    $videoHistory['status'] = Config::get('constant.ACTIVE_FLAG');
                    $videoHistory->save();

                    // Commit to DB
                    DB::commit();

                    // Save like success message
                    return response()->json([
                        'status' => 1,
                        'message' => trans('api-message.VIDEO_HISTORY_SAVED_SUCCESSFULLY'),
                    ]);
                }
        } catch(\Exception $e){
            DB::rollback();
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
     * Get video comment 
     *
     * @param Request $request
     * @return json response from server
     * @throws Exception If there was a any exeption error
     * @Get("video/{$videoId}/comments/{commentId}")
     */

    public function getVideoComment(Request $request, $videoId ,$commentId)
    {
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
            $userId = $request->user()->id; 

            // Get video 
            $video = VideoBookVideo::find($videoId);

            if(empty($video)) {
                return response()->json([
                    'status' => 0,
                    'message' => trans('api-message.VIDEO_NOT_FOUND'),
                ]);
            }

            $videoBookId = $video->e_video_book_id;

            // Get video series which purchased by user
            $getSeries = VideoBook::find($videoBookId);

            if ($getSeries->user_id !== $userId){
                // Get video series which purchased by user
                $getSeries = VideoBook::with('author')->withCount(['users' => function ($query) use ($user) {
                    $query->where('users.id', $user->id);
                }])->find($videoBookId);


                if(empty($getSeries) || $getSeries->users_count == 0)
                {
                    return response()->json([
                        'status' => 0,
                        'message' => trans('api-message.USER_NOT_PURCHASED_VIDEOSERIES'),
                    ]);
                }
            }

            if($commentId == 0)
             {

                    $getVideoCommentsDetail = $video->comments()->with('videoCommentedUser')->where('type',Config::get('constant.PARENT'))->where('parent_id',$commentId)->orderBy('created_at','DESC')->paginate(Config::get('constant.COMMENT_LIST_PER_PAGE'));
                    
             }
             else{

                    $videoCommentExists = VideoComment::where('parent_id',$commentId)->where('e_video_book_video_id',$videoId)->first();
 
                    if(!empty($videoCommentExists) && $videoCommentExists ->exists())
                    {

                        $getVideoCommentsDetail = $video->comments()->with('videoCommentedUser')->where('type',Config::get('constant.REPLIED'))->where('parent_id',$commentId)->orderBy('created_at','DESC')->paginate(Config::get('constant.COMMENT_LIST_PER_PAGE'));
                    }
                    else{

                        // Return error message when comment id not exists
                        return response()->json([
                            'status' => 1,
                            'message' => trans('api-message.COMMENT_LIST_FETCHED_SUCCESSFULLY'),
                            'data' => []
                        ],200);
                    }
                } 
              
            // All good so retuen response success
            return response()->json([
                'status' => 1,
                'message' => trans('api-message.COMMENT_LIST_FETCHED_SUCCESSFULLY'),
                'total_count' => $getVideoCommentsDetail->total(),
                'next' => $getVideoCommentsDetail->hasMorePages(),
                'previous' => $getVideoCommentsDetail->onFirstPage() ? false : true,
                'data' => CommentResource::collection($getVideoCommentsDetail),     
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

    public function increaseVideoDownloadCount(Request $request, $id){
        try{
            $user = $request->user();
            $userId = $request->user()->id;

            // Get video
            $video = VideoBookVideo::find($id);

            if(empty($video)) {
                return response()->json([
                    'status' => 0,
                    'message' => trans('api-message.VIDEO_NOT_FOUND'),
                ]);
            }

            $video->total_download++;
            $video->save();

            // Save like success message
            return response()->json([
                'status' => 1,
                'message' => trans('api-message.COUNT_ADD_SUCCESSFULLY'),
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
}
