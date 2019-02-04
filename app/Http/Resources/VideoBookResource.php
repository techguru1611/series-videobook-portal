<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;
use Config;
use Storage;

class VideoBookResource extends JsonResource
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
            'title' => $this->title,
            'descr' => $this->descr,
            'price' => $this->price,
            'path' => ($this->path === null || $this->path == '') ? '' : Storage::disk('FILESYSTEM_DRIVER')->url(Config::get('constant.SERIES_THUMB_PHOTO_UPLOAD_PATH') . $this->path),
            'totalLength' => $this->videos->where('type', Config::get('constant.SERIES_VIDEO'))->where('is_approved', Config::get('constant.APPROVED_VIDEO_FLAG'))->where('status', Config::get('constant.ACTIVE_FLAG'))->sum('lengh')
        ];
    }
}
