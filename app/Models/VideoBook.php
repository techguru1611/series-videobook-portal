<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Config;
use DB;
use Illuminate\Database\Eloquent\SoftDeletes;

class VideoBook extends Model
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
    protected $dates = ['deleted_at'];

    /**
     * The database table used by the model.
     *
     * @var string
     */
    protected $table = 'e_video_book';

    /**
     * Get Video Book Count
     */
    public static function getVideoBookCount()
    {
        return VideoBook::where('status', '<>', Config::get('constant.DELETED_FLAG'))->count();
    }

    /**
     * Get Active Video Book
     */
    public static function getActiveVideoBook()
    {
        return VideoBook::where('status', Config::get('constant.ACTIVE_FLAG'))->get();
    }

    /**
     * Get Active Count using find By ID 
     */
    public static function getActiveIdCount($id)
    {
        return VideoBook::where('status', Config::get('constant.ACTIVE_FLAG'))->where('video_category_id', $id)->count();
    }
    /**
     * Get Video Book
     */
    public static function getVideoBook()
    {
        return VideoBook::all();
    }

    /**
     * Search Video Category Title in Video Book table
     */
    public function scopeSearchVideoCategoryTitle($query, $value)
    {
        return $query->Where('video_category.title', 'LIKE', "%$value%");
    }

    /**
     * Search Video Sub Category Title in Video Book table
     */
    public function scopeSearchVideoSubCategoryTitle($query, $value)
    {
        return $query->orWhere('video_sub_category.title', 'LIKE', "%$value%");
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

    /**
     * Search Video Book Description in Video Book table
     */
    public function scopeSearchVideoBookDescription($query, $value)
    {
        return $query->orWhere('e_video_book.descr', 'LIKE', "%$value%");
    }

    /**
     * Search Video Book Price in Video Book table
     */
    public function scopeSearchVideoBookPrice($query, $value)
    {
        return $query->orWhere('e_video_book.price', 'LIKE', "%$value%");
    }

    /**
     * Search Video Book Price in Video Book table
     */
    public function scopeSearchVideoBookTotalDownload($query, $value)
    {
        return $query->orWhere('e_video_book.total_download', 'LIKE', "%$value%");
    }

    /**
     * Search Video Book Author Profit in Video Book table
     */
    public function scopeSearchVideoBookAuthorProfit($query, $value)
    {
        return $query->orWhere('e_video_book.author_profit_in_percentage', 'LIKE', "%$value%");
    }

    /**
     * Search Status Value in Video Book table
     */
    public function scopeSearchVideoBookStatus($query, $value)
    {
        return $query->orWhere('e_video_book.status', 'LIKE', "%$value%");
    }

    /**
     * Update video category status
     * active/inactive
     */
    public static function updateVideoBookStatus($id)
    {
        try {
            DB::statement(
                DB::raw("UPDATE e_video_book SET status =
                        (
                            CASE
                                WHEN status='active'
                                THEN 'inactive'
                                ELSE 'active'
                            END
                        )
                        WHERE id='" . $id . "'"
                ), array()
            );
            return 1;
        } catch (\Exception $e) {
            return 0;
        }
    }

    /**
     * Get the category of the video book.
     */
    public function category()
    {
        return $this->belongsTo('App\Models\VideoCategory', 'video_category_id');
    }

    /**
     * Get the videos for the video book.
     */
    public function allVideos()
    {
        return $this->hasMany('App\Models\VideoBookVideo', 'e_video_book_id');
    }

    /**
     * Get the videos for the video book.
     */
    public function videos()
    {
        return $this->hasMany('App\Models\VideoBookVideo', 'e_video_book_id')->where('type',Config::get('constant.SERIES_VIDEO'));
    }

    /**
     * Get the author of the video book.
    */
    public function author()
    {
        return $this->belongsTo('App\Models\User', 'user_id')->withTrashed();
    }

    /**
     * The users that belong to the video book.
     */
    public function users()
    {
        return $this->belongsToMany('App\Models\User', 'user_purchased_e_video_books', 'e_video_book_id', 'user_id');
    }
    
    /**
     * Get the intro videos for the video book.
     */
    public function introVideos()
    {
        return $this->hasMany('App\Models\VideoBookVideo', 'e_video_book_id')->where('type',Config::get('constant.INTRO_VIDEO'));
    }

    /**
     * Get viewed series 
    */
    public function seriesViewedByUser()
    {
        return $this->belongsToMany('App\Models\User', 'user_history','e_video_book_id','user_id');
    }
    
}
