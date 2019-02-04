<?php

namespace App\models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Config;
use DB;

class Setting extends Model
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
    protected $table = 'settings';

    /**
     * Get Cms active Count
     */
    public static function getSettingCount(){
        return Setting::where('status', '<>', Config::get('constant.DELETED_FLAG'))->count();
    }

    /**
     * Get settings Active
     */
    public static function getActiveCms()
    {
        return Setting::where('status', Config::get('constant.ACTIVE_FLAG'))->get();
    }

    /**
     *  Get all Record 
     */
    public static function getSetting()
    {
        return Setting::all();
    }

    /**
     * Search Video series Title in settings table
     */
    public function scopeSearchVideoSeriesTitle($query, $value)
    {
        return $query->where('e_video_book.title', 'LIKE', "%$value%");
    }
    
    /**
     * Search Settings Name in settings table
     */
    public function scopeSearchSettingName($query, $value)
    {
        return $query->orWhere('settings.title', 'LIKE', "%$value%");
    }

    /**
     * Search Status Value in Settings table
     */
    public function scopeSearchSettingStatus($query, $value)
    {
        return $query->orWhere('settings.status', 'LIKE', "%$value%");
    }

    /**
     * Update Settings status
     * active/inactive
     */
    public static function updateSettingStatus($id)
    {
        try {
            DB::statement(
                DB::raw("UPDATE settings SET status =
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
