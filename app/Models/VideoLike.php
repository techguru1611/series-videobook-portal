<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Config;
use DB;
use Illuminate\Database\Eloquent\SoftDeletes;

class VideoLike extends Model
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
    protected $dates = ['deleted_at', 'updated_at','created_at'];

    /**
     * The database table used by the model.
     *
     * @var string
     */
    protected $table = 'video_likes';

    /**
     * Get video like by user 
    */           
    public function videoLikedUser()
    {
        return $this->belongsTo('App\Models\User','user_id');
    }



}
