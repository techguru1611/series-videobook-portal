<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Config;
use DB;
use Illuminate\Database\Eloquent\SoftDeletes;

class VideoBookVideo extends Model
{
    use SoftDeletes;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $guarded = [];

    /**
     * The attributes that should be hidden for arrays.
     *
     * @var array
     */
    protected $hidden = [];

    /**
     * The attributes that are dates
     *
     * @var array
     */
    protected $dates = ['deleted_at', 'approved_at'];

    /**
     * The database table used by the model.
     *
     * @var string
     */
    protected $table = 'e_video_book_videos';

    /**
     * Get the videos that owns the video book.
     */
    public function videoBook()
    {
        return $this->belongsTo('App\Models\VideoBook', 'e_video_book_id');
    } 

    /**
     * Get video likes of user.
    */
    public function videoLikesByUser()
    {   
        return $this->belongsToMany('App\Models\User', 'video_likes','video_id','user_id')->where('action', Config::get('constant.LIKE'));
    }

    /**
     * Get video unlike of user.
    */
    public function videoUnlikesByUser()
    {   
        return $this->belongsToMany('App\Models\User', 'video_likes','video_id','user_id')->where('action', Config::get('constant.UNLIKE'));
    }

    /**
     * Get video history of user.
    */
    public function userVideoHistory()
    {
        return $this->belongsToMany('App\Models\User', 'user_video_watch_history', 'e_video_book_videos_id','user_id');
    }

    /**
     * Get video comment of user.
    */
    public function videoCommentByUser()
    {
        return $this->belongsToMany('App\Models\User', 'video_comment', 'e_video_book_video_id','comment_by');
    }

    /**
     * Get video comment.
    */
    public function comments()
    {
        return $this->hasMany('App\Models\VideoComment','e_video_book_video_id');
    }

    /**
     * Get video like.
    */
    public function likes()
    {
        return $this->hasMany('App\Models\VideoLike','video_id');
    }

    /**
     * Get video watch history user.
    */
    public function videoWatchedByUser()
    {
        return $this->belongsToMany('App\Models\User','user_video_watch_history','e_video_book_videos_id','user_id')->withPivot('watched_till','is_completed','last_watched_at');
    }

    /**
     * Search Video Book Author in Video Book table
     */
    public function scopeSearchVideoAuthor($query, $value)
    {
        return $query->orWhere('users.full_name', 'LIKE', "%$value%");
    }

    /**
     * Search Video Book Title in Video Book table
     */
    public function scopeSearchVideoBookTitle($query, $value)
    {
        return $query->orWhere('e_video_book.title', 'LIKE', "%$value%");
    }

    public function scopeSearchVideoTitle($query, $value)
    {
        return $query->orWhere('e_video_book_videos.title', 'LIKE', "%$value%");
    }

    public function scopeSearchVideoTranscodeStatus($query, $value)
    {
        return $query->orWhere('e_video_book_videos.is_transacoded', 'LIKE', "%$value%");
    }

    public static function updateVideoBookVideosStatus($id){
        try {
            $user_id = auth()->user()->id;
            DB::statement(
                DB::raw("UPDATE e_video_book_videos SET is_approved =
                        (
                            CASE
                                WHEN is_approved =1
                                THEN 0
                                ELSE 1
                            END
                        ),
                        approved_by = $user_id
                        WHERE id='" . $id . "'"
                ), array()
            );
            return 1;
        } catch (\Exception $e) {
            return 0;
        }
    }



}
