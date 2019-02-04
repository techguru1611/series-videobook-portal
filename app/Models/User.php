<?php

namespace App\Models;

use Config;
use DB;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Tymon\JWTAuth\Contracts\JWTSubject;

class User extends Authenticatable implements JWTSubject
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
    protected $hidden = [
        'password', 'remember_token', 'activation_token',
    ];

    /**
     * The attributes that are dates
     *
     * @var array
     */
    protected $dates = ['deleted_at', 'login_otp_created_at', 'birth_date'];

    /**
     * The database table used by the model.
     *
     * @var string
     */
    protected $table = 'users';

    /**
     * Set the user's password
     *
     * @param string  $password
     * @return void
     */
    public function setPasswordAttribute($password)
    {
        $this->attributes['password'] = bcrypt($password);
    }

    /**
     * Get the identifier that will be stored in the subject claim of the JWT.
     *
     * @return mixed
     */
    public function getJWTIdentifier()
    {
        return $this->getKey();
    }

    /**
     * Return a key value array, containing any custom claims to be added to the JWT.
     *
     * @return array
     */
    public function getJWTCustomClaims()
    {
        return [];
    }

    /**
     * The roles that belong to the user.
     */
    public function roles()
    {
        return $this->belongsToMany(Role::class)->withTimestamps();
    }

    /**
     * Check that user has given role or not
     *
     * @param array $role
     * @return bool
     */
    public function hasRole($role)
    {
        return null !== $this->roles()->where('slug', $role)->first();
    }

    /**
     * Check that user has given role or not
     *
     * @param array $role
     * @return bool
     */
    public function hasAnyRole($role)
    {
        return null !== $this->roles()->whereIn('slug', $role)->first();
    }

    /**
     * Get User Count
     */
    public static function getUserCount()
    {
        return User::where('status', '<>', Config::get('constant.DELETED_FLAG'))->count();
    }

    /**
     * Search User Full name in User table
     */
    public function scopeSearchUserFullNameName($query, $value)
    {
        return $query->Where('full_name', 'LIKE', "%$value%");
    }

    /**
     * Search User name in User table
     */
    public function scopeSearchUserName($query, $value)
    {
        return $query->orWhere('username', 'LIKE', "%$value%");
    }

    /**
     * Search User name in User table
     */
    public function scopeSearchEmail($query, $value)
    {
        return $query->orWhere('email', 'LIKE', "%$value%");
    }

    /**
     * Search User name in User table
     */
    public function scopeSearchPhoneNo($query, $value)
    {
        return $query->orWhere(DB::raw('CONCAT(country_code, " ", phone_no)'), 'LIKE', "%$value%");
    }

    /**
     * Search User name in User table
     */
    public function scopeSearchGender($query, $value)
    {
        return $query->orWhere('gender', 'LIKE', "%$value%");
    }

    /**
     * Search User name in User table
     */
    public function scopeSearchUserLevel($query, $value)
    {
        return $query->orWhere('title', 'LIKE', "%$value%");
    }

    /**
     * Search Status Value in user table
     */
    public function scopeSearchStatus($query, $value)
    {
        return $query->orWhere('users.status', 'LIKE', "%$value%");
    }

    /**
     * Get user detail by email
     */
    public static function findUserByEmail($email)
    {
        return User::where('email', $email)->withTrashed()->first();
    }

    /**
     * Update user status
     * active/inactive
     */
    public static function updateUserStatus($id)
    {
        try {
            DB::statement(
                DB::raw("UPDATE users SET status =
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

    public static function insertUpdate($data)
    {
        if (isset($data['id']) && !empty($data['id']) && $data['id'] > 0) {
            $user = User::find($data['id']);
            $user->update($data);
            return User::find($data['id']);
        } else {
            return User::create($data);
        }
    }

    /**
     * The videoCategory user
     */
    public function videoCategories()
    {
        return $this->belongsToMany('App\Models\VideoCategory', 'user_category', 'user_id', 'video_category_id');
    }

    /**
     * The series that purchased by user
     */
    public function userPurchasedSeries()
    {
        return $this->belongsToMany('App\Models\VideoBook', 'user_purchased_e_video_books', 'user_id', 'e_video_book_id');
    }

    public function userSubscribe()
    {
        return $this->belongsToMany('App\Models\User', 'user_favorite_list', 'user_id', 'author_id');
    }

    public function userSubscriber()
    {
        return $this->belongsToMany('App\Models\User', 'user_favorite_list', 'author_id', 'user_id');
    }

    /**
     * Get user liked video series 
    */           
    public function userVideoLikes()
    {
        return $this->belongsToMany('App\Models\VideoBookVideo', 'video_likes', 'user_id', 'video_id');
    }
    
    /**
     * Get viewed series history of user.
    */
    public function userHistory()
    {
        return $this->belongsToMany('App\Models\VideoBook', 'user_history', 'user_id', 'e_video_book_id')->orderBy('user_history.created_at','DESC');
    }

    /**
     * Get comment by user 
    */           
    public function userComments()
    {
        return $this->hasMany('App\Models\VideoComment','comment_by');
    }


    /**
     * Get video watch history
    */           
    public function userVideoHistory()
    {
        //return $this->hasMany(UserVideoWatchHistory::class, 'user_id');
        return $this->belongsToMany('App\Models\VideoBookVideo', 'user_video_watch_history', 'user_id', 'e_video_book_videos_id')->withPivot('watched_till','is_completed','last_watched_at')->withTrashed();
    }
    
}
