<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Config;
use DB;

class Cms extends Model
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
    protected $table = 'cms';

    /**
     * Get Cms active Count
     */
    public static function getCmsCount(){
        return Cms::where('status', '<>', Config::get('constant.DELETED_FLAG'))->count();
    }

    /**
     * Get Cms Active
     */
    public static function getActiveCms()
    {
        return Cms::where('status', Config::get('constant.ACTIVE_FLAG'))->get();
    }

    /**
     * Cms Get all Record 
     */
    public static function getCms()
    {
        return Cms::all();
    }
    
    /**
     * Search Cms Name in Cms table
     */
    public function scopeSearchCmsName($query, $value)
    {
        return $query->Where('title', 'LIKE', "%$value%");
    }

    /**
     * Search Status Value in Cms table
     */
    public function scopeSearchCmsStatus($query, $value)
    {
        return $query->orWhere('status', 'LIKE', "%$value%");
    }

    /**
     * Update Cms status
     * active/inactive
     */
    public static function updateCmsStatus($id)
    {
        try {
            DB::statement(
                DB::raw("UPDATE cms SET status =
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
