<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Config;
use DB;
use Illuminate\Database\Eloquent\SoftDeletes;

class VideoCategory extends Model
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
    protected $table = 'video_category';

    /**
     * Get Video Category Count
     */
    public static function getVideoCategoryCount()
    {
        return VideoCategory::where('status', '<>', Config::get('constant.DELETED_FLAG'))->count();
    }

    /**
     * Get Video Active Category
     */
    public static function getActiveVideoCategory()
    {
        return VideoCategory::where('status', Config::get('constant.ACTIVE_FLAG'))->get();
    }

    /**
     * Get Video Category
     */
    public static function getVideoCategory()
    {
        return VideoCategory::all();
    }
    
    /**
     * Search Video Category Title in Video Category table
     */
    public function scopeSearchVideoCategoryTitle($query, $value)
    {
        return $query->Where('title', 'LIKE', "%$value%");
    }

    /**
     * Search Video Category Description in Video Category table
     */
    public function scopeSearchVideoCategoryDescription($query, $value)
    {
        return $query->orWhere('descr', 'LIKE', "%$value%");
    }

    /**
     * Search Status Value in video category table
     */
    public function scopeSearchVideoCategoryStatus($query, $value)
    {
        return $query->orWhere('status', 'LIKE', "%$value%");
    }

    public function series() {
        return $this->hasMany('App\Models\VideoBook', 'video_category_id');
    }

    /**
     * Update video category status
     * active/inactive
     */
    public static function updateVideoCategoryStatus($id)
    {
        try {
            DB::statement(
                DB::raw("UPDATE video_category SET status =
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

    public static function getVidoCategoryDataCount($searchByName){
        $videoData = VideoCategory::where('status',Config::get('constant.ACTIVE_FLAG'));

        // search By Locality
        if ($searchByName != ''){
            $videoData = $videoData->where('title','LIKE',"%$searchByName%");
        }

        return $videoData->count();
    }

    /**
     * get video Category Data
     *
     * @param $limit
     * @param $offset
     * @param $sort
     * @param $order
     * @param $searchByName
     * @return mixed
     */
    public static function getVideoCategoryList($limit, $offset, $sort, $order, $searchByName){
        $videoData = VideoCategory::where('status',Config::get('constant.ACTIVE_FLAG'));

        // search By Locality
        if ($searchByName != ''){
            $videoData = $videoData->where('title','LIKE',"%$searchByName%");
        }

        return $videoData->orderBy($sort, $order)
            ->take($limit)
            ->offset($offset)
            ->get();
    }

    public function users(){
        return $this->belongsToMany('App\Models\User', 'user_category','video_category_id','user_id');

    }
    
}
