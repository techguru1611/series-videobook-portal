<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Config;
use DB;
use Illuminate\Database\Eloquent\SoftDeletes;

class VideoAdvertisement extends Model
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
     * The database table used by the model.
     *
     * @var string
     */
    protected $table = 'video_advertisement';

    /**
     * Get Video Advertisement Count
     */
    public static function getVideoAdvertisementCount()
    {
        return VideoAdvertisement::where('status', '<>', Config::get('constant.DELETED_FLAG'))->count();
    }

    /**
     * Get Active Video video advertisement
     */
    public static function getActiveVideoAdvertisement()
    {
        return VideoAdvertisement::where('status', Config::get('constant.ACTIVE_FLAG'))->get();
    }

    /**
     * Search Video Advertisement Title in Video Advertisement table
     */
    public function scopeSearchVideoAdvertisementTitle($query, $value)
    {
        return $query->Where('title', 'LIKE', "%$value%");
    }

    /**
     * Search Video Advertisement Description in Video Advertisement table
     */
    public function scopeSearchVideoAdvertisementDescription($query, $value)
    {
        return $query->orWhere('descr', 'LIKE', "%$value%");
    }

    /**
     * Search Status Value in Video Advertisement table
     */
    public function scopeSearchVideoAdvertisementStatus($query, $value)
    {
        return $query->orWhere('status', 'LIKE', "%$value%");
    }


     /**
     * Update video category status
     * active/inactive
     */
    public static function updateVideoAdvertisementStatus($id)
    {
        try {
            DB::statement(
                DB::raw("UPDATE video_advertisement SET status =
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
