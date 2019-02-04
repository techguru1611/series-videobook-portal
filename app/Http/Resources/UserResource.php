<?php

namespace App\Http\Resources;

use Carbon\Carbon;
use Illuminate\Http\Resources\Json\JsonResource;
use Storage;
use Config;

class UserResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array
     */
    public function toArray($request)
    {
        return [
            'id' => $this->id,
            'email' => (string) $this->email,
            'fullName' => (string) $this->full_name,
            'bio' => (string) $this->bio,
            'userName' => (string) $this->username,
            'googleId' => (string) $this->google_id,
            'facebookId' => (string) $this->facebook_id,
            'countryCode' => (string) $this->country_code,
            'phoneNo' => (string) $this->phone_no,
            'birthDate' => (is_null($this->birth_date) || empty($this->birth_date)) ? '' : Carbon::parse($this->birth_date)->format('Y-m-d'),
            'isVerified' => ($this->is_verified) ? true : false ,
            'originalProfilePicturePath' => ($this->photo === null || $this->photo == '') ? '' : (Storage::disk('public')->exists(Config::get('constant.USER_ORIGINAL_PHOTO_UPLOAD_PATH') . $this->photo) ? Storage::disk('public')->url(Config::get('constant.USER_ORIGINAL_PHOTO_UPLOAD_PATH') . $this->photo) : (Storage::disk('s3')->exists(Config::get('constant.USER_ORIGINAL_PHOTO_UPLOAD_PATH') . $this->photo) ? Storage::disk('s3')->url(Config::get('constant.USER_ORIGINAL_PHOTO_UPLOAD_PATH') . $this->photo) : '')),
            'thumbProfilePicturePath' => ($this->photo === null || $this->photo == '') ? '' : (Storage::disk('public')->exists(Config::get('constant.USER_THUMB_PHOTO_UPLOAD_PATH') . $this->photo) ? Storage::disk('public')->url(Config::get('constant.USER_THUMB_PHOTO_UPLOAD_PATH') . $this->photo) :(Storage::disk('s3')->exists(Config::get('constant.USER_THUMB_PHOTO_UPLOAD_PATH') . $this->photo) ? Storage::disk('s3')->url(Config::get('constant.USER_THUMB_PHOTO_UPLOAD_PATH') . $this->photo) : '')),          
            'status' => $this->status,
            'interestedCategory' => UserCategoryResource::collection($this->videoCategories),
            'isCompleteProfileForAuthor' => (!empty($this->birth_date) && !empty($this->bio)) ? true : false,
            'isAddedPaymentDetails' => (!empty($this->birth_date) && !empty($this->bio) && !empty($this->stripe_account_id)) ? true : false,
            'paymentDetails' => $this->mergeWhen(!empty($this->card), $this->card),
            'updatedAt' => Carbon::parse($this->updated_at)->timestamp * 1000,
            'createdAt' => Carbon::parse($this->created_at)->timestamp * 1000,
        ];
    }
}
