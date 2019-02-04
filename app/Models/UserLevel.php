<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Config;
use DB;
use Illuminate\Database\Eloquent\SoftDeletes;

class UserLevel extends Model
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
    protected $table = 'user_level';

    /**
     * The users that belong to the user level.
     */
    public function user()
    {
        return $this->hasMany(User::class);
    }

    /**
     * Get User Level Count
     */
    public static function getUserLevelCount()
    {
        return UserLevel::where('status', '<>', Config::get('constant.DELETED_FLAG'))->count();
    }

    /**
     * Search User Level Title in User Level table
     */
    public function scopeSearchUserLevelTitle($query, $value)
    {
        return $query->Where('title', 'LIKE', "%$value%");
    }

    /**
     * Search User Level Purchase in User Level table
     */
    public function scopeSearchUserLevelPurchase($query, $value)
    {
        return $query->orWhere('purchase', 'LIKE', "%$value%");
    }

    /**
     * Search User Level Watched in minutes in User Level table
     */
    public function scopeSearchUserLevelPurchasedVideoLength($query, $value)
    {
        return $query->orWhere('purchased_video_length', 'LIKE', "%$value%");
    }

    /**
     * Search Status Value in user level table
     */
    public function scopeSearchUserLevelStatus($query, $value)
    {
        return $query->orWhere('status', 'LIKE', "%$value%");
    }

    /**
     * Update user level status
     * active/inactive
     */
    public static function updateUserLevelStatus($id)
    {
        try {
            DB::statement(
                DB::raw("UPDATE user_level SET status =
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
