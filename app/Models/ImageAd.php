<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;

class ImageAd extends Model
{
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
    protected $table = 'img_advertisement';

    /**
     * Get Video Category Count
     */
    public static function getImgAdCount()
    {
        return ImageAd::where('status', '<>', Config::get('constant.DELETED_FLAG'))->count();
    }

    /**
     * Get Video Active Category
     */
    public static function getActiveImgAd()
    {
        return ImageAd::where('status', Config::get('constant.ACTIVE_FLAG'))->get();
    }

    /**
     * Get Video Category
     */
    public static function getImgAd()
    {
        return VideoCategory::all();
    }

    /**
     * Search Video Category Title in Video Category table
     */
    public function scopeSearchImgAdTitle($query, $value)
    {
        return $query->Where('title', 'LIKE', "%$value%");
    }

    /**
     * Search Status Value in video category table
     */
    public function scopeSearchImgAdStatus($query, $value)
    {
        return $query->orWhere('status', 'LIKE', "%$value%");
    }

    /**
     * Update image Adevertisaments status
     * active/inactive
     */
    public static function updateImageAdStatus($id)
    {
        try {
            DB::statement(
                DB::raw("UPDATE img_advertisement SET status =
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
        $imgAd = ImageAd::where('status',Config::get('constant.ACTIVE_FLAG'));

        // search By Locality
        if ($searchByName != ''){
            $imgAd = $imgAd->where('title','LIKE',"%$searchByName%");
        }

        return $imgAd->count();
    }
}
