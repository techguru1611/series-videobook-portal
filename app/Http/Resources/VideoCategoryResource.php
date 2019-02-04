<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;
use Config;
use Storage;
use Carbon\Carbon;

class VideoCategoryResource extends JsonResource
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
            'name' => (string) $this->title,
            'descr' => (string) $this->descr,
            'image' => ($this->image === null || $this->image == '') ? '' : (Storage::disk('public')->exists(Config::get('constant.VIDEO_CATEGORY_THUMB_PHOTO_UPLOAD_PATH') . $this->image) ? Storage::disk('public')->url(Config::get('constant.VIDEO_CATEGORY_THUMB_PHOTO_UPLOAD_PATH') . $this->image) : (Storage::disk('s3')->exists(Config::get('constant.VIDEO_CATEGORY_THUMB_PHOTO_UPLOAD_PATH') . $this->image) ? Storage::disk('s3')->url(Config::get('constant.VIDEO_CATEGORY_THUMB_PHOTO_UPLOAD_PATH') . $this->image) : '')),
            'seriesCount' => $this->series_count,
            'isSelectedByUser' => (isset($this->users) && count($this->users) > 0 ) ? true : false,
             // 'userCount'=>(string)$this->users_count,
        ];
    }
}
