<?php

namespace App\models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Config;
use DB;

class ContactUs extends Model
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
    protected $table = 'contact_us';

    /**
     * Get Cms active Count
     */
    public static function getContactUsCount(){
        return ContactUs::count();
    }

    /**
     * Search Cms Name in Cms table
     */
    public function scopeSearchContactEmail($query, $value)
    {
        return $query->Where('email', 'LIKE', "%$value%");
    }

    /**
     * Search Status Value in Cms table
     */
    public function scopeSearchContactMessage($query, $value)
    {
        return $query->orWhere('description', 'LIKE', "%$value%");
    }

}
