<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;
use Config;
use Storage;

class AuthorResource extends JsonResource
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
            'name' => $this->full_name,
            'email' => $this->email,
            'image' => ($this->photo === null || $this->photo == '') ? '' : (Storage::disk('public')->exists(Config::get('constant.USER_THUMB_PHOTO_UPLOAD_PATH') . $this->photo) ? Storage::disk('public')->url(Config::get('constant.USER_THUMB_PHOTO_UPLOAD_PATH') . $this->photo) : Storage::disk('s3')->url(Config::get('constant.USER_THUMB_PHOTO_UPLOAD_PATH') . $this->photo)),
            'bio' => (string)$this->bio,
            'isSubscribed' => (!empty($this->userSubscriber->toArray())) ? true : false,
        ];
    }
}
