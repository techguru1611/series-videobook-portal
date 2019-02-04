<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Config;
use DB;
use Illuminate\Database\Eloquent\SoftDeletes;

class VideoSubCategory extends Model
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
    protected $table = 'video_sub_category';

    /**
     * Get Video Sub Category Count
     */
    public static function getVideoSubCategoryCount()
    {
        return VideoSubCategory::where('status', '<>', Config::get('constant.DELETED_FLAG'))->count();
    }

    /**
     * Get Video Sub Category From Category
     */
    public static function getVideoSubCategoryFromCategory($videoCategoryId)
    {
        return VideoSubCategory::where('video_category_id', $videoCategoryId)->get();
    }

    /**
     * Search Video Sub Category Title in Video Sub Category table
     */
    public function scopeSearchVideoSubCategoryTitle($query, $value)
    {
        return $query->Where('title', 'LIKE', "%$value%");
    }

    /**
     * Search Video Sub Category Description in Video Sub Category table
     */
    public function scopeSearchVideoSubCategoryDescription($query, $value)
    {
        return $query->orWhere('descr', 'LIKE', "%$value%");
    }

    /**
     * Search Status Value in video category table
     */
    public function scopeSearchVideoSubCategoryStatus($query, $value)
    {
        return $query->orWhere('video_sub_category.status', 'LIKE', "%$value%");
    }

    public static function getVideoSubCategoryFromCategoryId ($videoCategoryId) {
        return VideoSubCategory::where('video_category_id', $videoCategoryId)->get([
            'id',
            'title'
        ]);
    }

    /**
     * Update video category status
     * active/inactive
     */
    public static function updateVideoSubCategoryStatus($id)
    {
        try {
            DB::statement(
                DB::raw("UPDATE video_sub_category SET status =
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
}
