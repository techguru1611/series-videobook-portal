<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;
use Storage;
use Config;

class VideoBookDetailResource extends JsonResource
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
            'name' => $this->title,
            'image' => ($this->image === null || $this->image == '') ? '' : (Storage::disk('public')->exists(Config::get('constant.VIDEO_SERIES_THUMB_PHOTO_UPLOAD_PATH') . $this->image) ? Storage::disk('public')->url(Config::get('constant.VIDEO_SERIES_THUMB_PHOTO_UPLOAD_PATH') . $this->image) : (Storage::disk('s3')->exists(Config::get('constant.VIDEO_SERIES_THUMB_PHOTO_UPLOAD_PATH') . $this->image) ? Storage::disk('s3')->url(Config::get('constant.VIDEO_SERIES_THUMB_PHOTO_UPLOAD_PATH') . $this->image) : '')),
            'authorId' => (isset($this->author->id) && !empty($this->author->id)) ? $this->author->id : 0,
            'authorName' => (isset($this->author->full_name) && !empty($this->author->full_name)) ? $this->author->full_name : 'Unknown Author',
            'desc' => $this->descr,
            'totalDuration' => $this->videos->where('is_approved', Config::get('constant.APPROVED_VIDEO_FLAG'))->where('status', Config::get('constant.ACTIVE_FLAG'))->sum('lengh'),
            'price' => $this->price,
            'isOwner' => (auth()->user()->id == $this->author->id) ? true : false,
            'userPurchased' => ($this->users_count > 0 ) ? true : (auth()->user()->id == $this->author->id) ? true : false,
            'categoryId' => $this->category->id,
            'categoryName' => $this->category->title,
            'downloadsCount' => $this->total_download, 
            'purchaseCount' => ($this->users_count === null || $this->users_count == '') ? 0 : $this->users_count,
        ];
    }
}
